<?php

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\TransactionError;
use App\Models\User;
use App\Services\TransactionErrorHandler;
use App\Services\TransactionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionErrorHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionErrorHandler $errorHandler;

    protected Transaction $transaction;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->errorHandler = new TransactionErrorHandler;

        // Create minimal required related records
        $branch = Branch::factory()->create();
        $counter = Counter::factory()->create(['branch_id' => $branch->id]);
        $this->user = User::factory()->create(['branch_id' => $branch->id]);
        $currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
        );

        $this->transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $branch->id,
            'till_id' => (string) $counter->id,
            'currency_code' => $currency->code,
            'type' => TransactionType::Buy,
            'status' => TransactionStatus::Processing,
        ]);
    }

    // =====================================================================
    // handleProcessingError TESTS
    // =====================================================================

    public function test_handle_processing_error_creates_error_record(): void
    {
        $errorType = TransactionErrorHandler::ERROR_TYPE_PROCESSING;
        $message = 'Database connection failed';
        $context = ['connection' => 'mysql', 'host' => 'localhost'];

        $result = $this->errorHandler->handleProcessingError(
            $this->transaction,
            $errorType,
            $message,
            $context
        );

        $this->assertTrue($result);

        $this->transaction->refresh();

        // Verify error was created (retry_count is 1 after incrementRetry)
        $this->assertDatabaseHas('transaction_errors', [
            'transaction_id' => $this->transaction->id,
            'error_type' => $errorType,
            'error_message' => $message,
            'retry_count' => 1,
            'max_retries' => 3,
        ]);

        // Verify transaction was transitioned to Failed
        $this->assertTrue($this->transaction->status->isFailed());
        $this->assertEquals($message, $this->transaction->failure_reason);
    }

    public function test_handle_processing_error_records_error_context(): void
    {
        $context = [
            'stack_trace' => 'Trace line 1...',
            'user_id' => $this->user->id,
            'extra_data' => ['key' => 'value'],
        ];

        $this->errorHandler->handleProcessingError(
            $this->transaction,
            TransactionErrorHandler::ERROR_TYPE_NETWORK,
            'Connection timeout',
            $context
        );

        $error = TransactionError::where('transaction_id', $this->transaction->id)->first();

        $this->assertNotNull($error);
        $this->assertEquals($context, $error->error_context);
    }

    public function test_handle_processing_error_returns_false_when_max_retries_exceeded(): void
    {
        // Create an error with max retries already reached
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Previous error',
            'retry_count' => 3,
            'max_retries' => 3,
        ]);

        $result = $this->errorHandler->handleProcessingError(
            $this->transaction,
            TransactionErrorHandler::ERROR_TYPE_NETWORK,
            'New error after max retries'
        );

        $this->assertFalse($result);
    }

    // =====================================================================
    // shouldRetry TESTS
    // =====================================================================

    public function test_should_retry_returns_false_when_no_errors(): void
    {
        $this->assertFalse($this->errorHandler->shouldRetry($this->transaction));
    }

    public function test_should_retry_returns_true_when_ready(): void
    {
        // Create error with retry count under max and next_retry_at in the past
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Test error',
            'retry_count' => 1,
            'max_retries' => 3,
            'next_retry_at' => now()->subSecond(), // In the past
        ]);

        $this->assertTrue($this->errorHandler->shouldRetry($this->transaction));
    }

    public function test_should_retry_returns_false_when_not_yet_time(): void
    {
        // Create error with retry count under max but next_retry_at in the future
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Test error',
            'retry_count' => 1,
            'max_retries' => 3,
            'next_retry_at' => now()->addHour(), // In the future
        ]);

        $this->assertFalse($this->errorHandler->shouldRetry($this->transaction));
    }

    public function test_should_retry_returns_false_when_max_retries_reached(): void
    {
        // Create error with retry count at max
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Test error',
            'retry_count' => 3,
            'max_retries' => 3,
            'next_retry_at' => now()->subSecond(),
        ]);

        $this->assertFalse($this->errorHandler->shouldRetry($this->transaction));
    }

    // =====================================================================
    // getNextRetryDelay TESTS (Exponential Backoff)
    // =====================================================================

    public function test_exponential_backoff_delays(): void
    {
        // Create error with retry count 0
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Test error',
            'retry_count' => 0,
            'max_retries' => 3,
        ]);

        // First retry: 100ms base * 2^0 = 100ms
        $this->assertEquals(100, $this->errorHandler->getNextRetryDelay($this->transaction));

        // Update retry count to 1
        $this->transaction->transactionErrors()->update(['retry_count' => 1]);

        // Second retry: 100ms base * 2^1 = 200ms
        $this->assertEquals(200, $this->errorHandler->getNextRetryDelay($this->transaction));

        // Update retry count to 2
        $this->transaction->transactionErrors()->update(['retry_count' => 2]);

        // Third retry: 100ms base * 2^2 = 400ms
        $this->assertEquals(400, $this->errorHandler->getNextRetryDelay($this->transaction));
    }

    public function test_get_next_retry_delay_returns_zero_when_no_errors(): void
    {
        $this->assertEquals(0, $this->errorHandler->getNextRetryDelay($this->transaction));
    }

    // =====================================================================
    // shouldMoveToDLQ TESTS
    // =====================================================================

    public function test_should_move_to_dlq_returns_false_when_not_failed(): void
    {
        // Transaction is in Processing status, not Failed
        $this->assertFalse($this->errorHandler->shouldMoveToDLQ($this->transaction));
    }

    public function test_should_move_to_dlq_returns_true_when_max_retries_exceeded(): void
    {
        // Transition to Failed
        $stateMachine = new TransactionStateMachine($this->transaction);
        $stateMachine->fail('Test failure');

        // Create error at max retries
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Max retries exceeded',
            'retry_count' => 3,
            'max_retries' => 3,
        ]);

        $this->assertTrue($this->errorHandler->shouldMoveToDLQ($this->transaction));
    }

    public function test_should_move_to_dlq_returns_true_for_non_retryable_error_types(): void
    {
        // Transition to Failed
        $stateMachine = new TransactionStateMachine($this->transaction);
        $stateMachine->fail('Validation failed');

        // Create validation error (non-retryable)
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_VALIDATION,
            'error_message' => 'Invalid transaction data',
            'retry_count' => 0,
            'max_retries' => 3,
        ]);

        $this->assertTrue($this->errorHandler->shouldMoveToDLQ($this->transaction));
    }

    public function test_should_move_to_dlq_returns_true_for_compliance_errors(): void
    {
        // Transition to Failed
        $stateMachine = new TransactionStateMachine($this->transaction);
        $stateMachine->fail('Compliance check failed');

        // Create compliance error (non-retryable)
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_COMPLIANCE,
            'error_message' => 'Compliance violation',
            'retry_count' => 0,
            'max_retries' => 3,
        ]);

        $this->assertTrue($this->errorHandler->shouldMoveToDLQ($this->transaction));
    }

    public function test_should_move_to_dlq_returns_false_when_retry_available(): void
    {
        // Transition to Failed
        $stateMachine = new TransactionStateMachine($this->transaction);
        $stateMachine->fail('Temporary failure');

        // Create error that can still be retried
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_NETWORK,
            'error_message' => 'Network timeout',
            'retry_count' => 1,
            'max_retries' => 3,
        ]);

        $this->assertFalse($this->errorHandler->shouldMoveToDLQ($this->transaction));
    }

    public function test_should_move_to_dlq_returns_false_when_no_unresolved_errors(): void
    {
        // Transition to Failed
        $stateMachine = new TransactionStateMachine($this->transaction);
        $stateMachine->fail('Failure with resolved error');

        // Create resolved error
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Resolved error',
            'retry_count' => 1,
            'max_retries' => 3,
            'resolved_at' => now(),
            'resolved_by' => $this->user->id,
        ]);

        $this->assertFalse($this->errorHandler->shouldMoveToDLQ($this->transaction));
    }

    // =====================================================================
    // markErrorResolved TESTS
    // =====================================================================

    public function test_mark_error_resolved_updates_error_record(): void
    {
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Test error',
            'retry_count' => 1,
            'max_retries' => 3,
        ]);

        $notes = 'Manually resolved by manager';

        $result = $this->errorHandler->markErrorResolved($this->transaction, $this->user->id, $notes);

        $this->assertTrue($result);

        $error = TransactionError::where('transaction_id', $this->transaction->id)->first();

        $this->assertNotNull($error->resolved_at);
        $this->assertEquals($this->user->id, $error->resolved_by);
        $this->assertEquals($notes, $error->resolution_notes);
    }

    public function test_mark_error_resolved_returns_false_when_no_errors(): void
    {
        $result = $this->errorHandler->markErrorResolved($this->transaction, $this->user->id, 'Notes');

        $this->assertFalse($result);
    }

    // =====================================================================
    // getTransactionErrors TESTS
    // =====================================================================

    public function test_get_transaction_errors_returns_all_errors(): void
    {
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_NETWORK,
            'error_message' => 'Network error',
            'retry_count' => 0,
            'max_retries' => 3,
        ]);

        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Processing error',
            'retry_count' => 1,
            'max_retries' => 3,
        ]);

        $errors = $this->errorHandler->getTransactionErrors($this->transaction);

        $this->assertCount(2, $errors);
    }

    public function test_get_transaction_errors_returns_empty_collection_when_no_errors(): void
    {
        $errors = $this->errorHandler->getTransactionErrors($this->transaction);

        $this->assertCount(0, $errors);
    }

    // =====================================================================
    // getRetryCount TESTS
    // =====================================================================

    public function test_get_retry_count_returns_current_count(): void
    {
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_PROCESSING,
            'error_message' => 'Test error',
            'retry_count' => 2,
            'max_retries' => 3,
        ]);

        $this->assertEquals(2, $this->errorHandler->getRetryCount($this->transaction));
    }

    public function test_get_retry_count_returns_zero_when_no_errors(): void
    {
        $this->assertEquals(0, $this->errorHandler->getRetryCount($this->transaction));
    }

    // =====================================================================
    // recordSuccessfulRetry TESTS
    // =====================================================================

    public function test_record_successful_retry_marks_error_resolved(): void
    {
        TransactionError::create([
            'transaction_id' => $this->transaction->id,
            'error_type' => TransactionErrorHandler::ERROR_TYPE_NETWORK,
            'error_message' => 'Network timeout',
            'retry_count' => 1,
            'max_retries' => 3,
            'next_retry_at' => now(),
        ]);

        $result = $this->errorHandler->recordSuccessfulRetry($this->transaction);

        $this->assertTrue($result);

        $error = TransactionError::where('transaction_id', $this->transaction->id)->first();

        $this->assertNotNull($error->resolved_at);
        $this->assertEquals('Retry successful', $error->resolution_notes);
    }

    public function test_record_successful_retry_returns_false_when_no_errors(): void
    {
        $result = $this->errorHandler->recordSuccessfulRetry($this->transaction);

        $this->assertFalse($result);
    }
}
