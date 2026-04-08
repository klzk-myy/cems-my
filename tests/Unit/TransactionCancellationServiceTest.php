<?php

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use App\Services\TransactionCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionCancellationService $cancellationService;

    protected Transaction $transaction;

    protected User $teller;

    protected User $manager;

    protected Branch $branch;

    protected Counter $counter;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cancellationService = new TransactionCancellationService(new MathService);

        // Create minimal required related records
        $this->branch = Branch::factory()->create();
        $this->counter = Counter::factory()->create(['branch_id' => $this->branch->id]);
        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
        );

        // Create teller user
        $this->teller = User::factory()->create([
            'branch_id' => $this->branch->id,
            'role' => UserRole::Teller,
        ]);

        // Create manager user
        $this->manager = User::factory()->create([
            'branch_id' => $this->branch->id,
            'role' => UserRole::Manager,
        ]);

        // Create a completed transaction
        $this->transaction = $this->createCompletedTransaction();
    }

    /**
     * Helper to create a completed transaction.
     */
    protected function createCompletedTransaction(array $overrides = []): Transaction
    {
        // Use a fixed amount that's guaranteed to be less than the position balance
        $amountLocal = '4250.00';  // This will give amount_foreign = 1000 with rate 4.25
        $rate = '4.250000';
        $amountForeign = bcdiv($amountLocal, $rate, 4);

        $transaction = Transaction::factory()->create(array_merge([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'till_id' => (string) $this->counter->id,
            'currency_code' => $this->currency->code,
            'type' => TransactionType::Buy,
            'status' => TransactionStatus::Completed,
            'is_refund' => false,
            'amount_local' => $amountLocal,
            'amount_foreign' => $amountForeign,
            'rate' => $rate,
        ], $overrides));

        // Create currency position for the transaction (use updateOrCreate to avoid duplicates)
        CurrencyPosition::updateOrCreate(
            [
                'currency_code' => $this->currency->code,
                'till_id' => (string) $this->counter->id,
            ],
            [
                'branch_id' => $this->branch->id,
                'balance' => '10000.0000',
                'avg_cost_rate' => '4.250000',
                'last_valuation_rate' => '4.250000',
            ]
        );

        return $transaction;
    }

    // =====================================================================
    // CANCELLATION TESTS
    // =====================================================================

    public function test_manager_can_cancel_draft_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Draft,
        ]);

        $result = $this->cancellationService->requestCancellation(
            $transaction,
            $this->manager,
            'Customer changed mind'
        );

        $this->assertTrue($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isCancelled());
        $this->assertEquals('Customer changed mind', $transaction->cancellation_reason);
    }

    public function test_manager_can_cancel_pending_approval_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::PendingApproval,
        ]);

        $result = $this->cancellationService->requestCancellation(
            $transaction,
            $this->manager,
            'Insufficient documentation'
        );

        $this->assertTrue($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isCancelled());
    }

    public function test_manager_can_cancel_approved_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Approved,
        ]);

        $result = $this->cancellationService->requestCancellation(
            $transaction,
            $this->manager,
            'Transaction no longer needed'
        );

        $this->assertTrue($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isCancelled());
    }

    public function test_manager_can_cancel_processing_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Processing,
        ]);

        $result = $this->cancellationService->requestCancellation(
            $transaction,
            $this->manager,
            'System error'
        );

        $this->assertTrue($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isCancelled());
    }

    public function test_manager_can_cancel_completed_transaction(): void
    {
        $transaction = $this->createCompletedTransaction();

        $result = $this->cancellationService->requestCancellation(
            $transaction,
            $this->manager,
            'Customer requested refund'
        );

        $this->assertTrue($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isCancelled());
    }

    public function test_teller_cannot_cancel_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Draft,
        ]);

        $result = $this->cancellationService->requestCancellation(
            $transaction,
            $this->teller,
            'Trying to cancel'
        );

        $this->assertFalse($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isDraft());
    }

    public function test_cannot_cancel_finalized_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Finalized,
        ]);

        $result = $this->cancellationService->requestCancellation(
            $transaction,
            $this->manager,
            'Too late'
        );

        $this->assertFalse($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isFinalized());
    }

    public function test_cannot_cancel_already_cancelled_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Cancelled,
        ]);

        $result = $this->cancellationService->requestCancellation(
            $transaction,
            $this->manager,
            'Already cancelled'
        );

        $this->assertFalse($result);
    }

    // =====================================================================
    // CANCEL ELIGIBILITY CHECKS
    // =====================================================================

    public function test_can_cancel_returns_true_for_cancellable_states(): void
    {
        $cancellableStatuses = [
            TransactionStatus::Draft,
            TransactionStatus::PendingApproval,
            TransactionStatus::Approved,
            TransactionStatus::Processing,
            TransactionStatus::Completed,
            TransactionStatus::Failed,
        ];

        foreach ($cancellableStatuses as $status) {
            $transaction = Transaction::factory()->create([
                'user_id' => $this->teller->id,
                'branch_id' => $this->branch->id,
                'status' => $status,
            ]);

            $this->assertTrue(
                $this->cancellationService->canCancel($transaction),
                "Expected {$status->value} to be cancellable"
            );
        }
    }

    public function test_can_cancel_returns_false_for_final_states(): void
    {
        $nonCancellableStatuses = [
            TransactionStatus::Finalized,
            TransactionStatus::Cancelled,
            TransactionStatus::Reversed,
        ];

        foreach ($nonCancellableStatuses as $status) {
            $transaction = Transaction::factory()->create([
                'user_id' => $this->teller->id,
                'branch_id' => $this->branch->id,
                'status' => $status,
            ]);

            $this->assertFalse(
                $this->cancellationService->canCancel($transaction),
                "Expected {$status->value} to not be cancellable"
            );
        }
    }

    // =====================================================================
    // REVERSAL TESTS
    // =====================================================================

    public function test_manager_can_reverse_completed_transaction_within_window(): void
    {
        // Transaction created just now (within 24-hour window)
        $transaction = $this->createCompletedTransaction([
            'created_at' => now()->subHours(2),
        ]);

        $result = $this->cancellationService->requestReversal(
            $transaction,
            $this->manager,
            'Customer dispute'
        );

        $this->assertTrue($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isReversed());
        $this->assertEquals('Customer dispute', $transaction->reversal_reason);
    }

    public function test_cannot_reverse_transaction_outside_window(): void
    {
        // Transaction created more than 24 hours ago
        $transaction = $this->createCompletedTransaction([
            'created_at' => now()->subHours(25),
        ]);

        $result = $this->cancellationService->requestReversal(
            $transaction,
            $this->manager,
            'Too late'
        );

        $this->assertFalse($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isCompleted());
    }

    public function test_cannot_reverse_non_completed_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Draft,
            'created_at' => now()->subHours(2),
        ]);

        $result = $this->cancellationService->requestReversal(
            $transaction,
            $this->manager,
            'Not completed'
        );

        $this->assertFalse($result);
    }

    public function test_cannot_reverse_refund_transaction(): void
    {
        // Original transaction
        $original = $this->createCompletedTransaction();

        // Refund transaction
        $refund = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Completed,
            'is_refund' => true,
            'original_transaction_id' => $original->id,
            'created_at' => now()->subHours(2),
        ]);

        $result = $this->cancellationService->requestReversal(
            $refund,
            $this->manager,
            'Reverse the refund'
        );

        $this->assertFalse($result);
    }

    public function test_teller_can_reverse_own_transaction_within_window(): void
    {
        // Transaction created by teller, within window
        $transaction = $this->createCompletedTransaction([
            'user_id' => $this->teller->id,
            'created_at' => now()->subHours(2),
        ]);

        $result = $this->cancellationService->requestReversal(
            $transaction,
            $this->teller,
            'My own mistake'
        );

        $this->assertTrue($result);
        $transaction->refresh();
        $this->assertTrue($transaction->status->isReversed());
    }

    public function test_teller_cannot_reverse_others_transaction(): void
    {
        // Transaction created by another teller
        $otherTeller = User::factory()->create([
            'branch_id' => $this->branch->id,
            'role' => UserRole::Teller,
        ]);

        $transaction = $this->createCompletedTransaction([
            'user_id' => $otherTeller->id,
            'created_at' => now()->subHours(2),
        ]);

        $result = $this->cancellationService->requestReversal(
            $transaction,
            $this->teller,
            'Trying to reverse others transaction'
        );

        $this->assertFalse($result);
    }

    // =====================================================================
    // CAN REVERSE ELIGIBILITY CHECKS
    // =====================================================================

    public function test_can_reverse_returns_true_for_completed_within_window(): void
    {
        $transaction = $this->createCompletedTransaction([
            'created_at' => now()->subHours(12),
        ]);

        $this->assertTrue($this->cancellationService->canReverse($transaction));
    }

    public function test_can_reverse_returns_false_for_completed_outside_window(): void
    {
        $transaction = $this->createCompletedTransaction([
            'created_at' => now()->subHours(48),
        ]);

        $this->assertFalse($this->cancellationService->canReverse($transaction));
    }

    public function test_can_reverse_returns_false_for_non_completed(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Processing,
        ]);

        $this->assertFalse($this->cancellationService->canReverse($transaction));
    }

    public function test_can_reverse_returns_false_for_already_reversed(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Reversed,
        ]);

        $this->assertFalse($this->cancellationService->canReverse($transaction));
    }

    // =====================================================================
    // CANCELLATION WINDOW TESTS
    // =====================================================================

    public function test_is_within_cancellation_window_returns_true_within_24_hours(): void
    {
        $transaction = $this->createCompletedTransaction([
            'created_at' => now()->subHours(12),
        ]);

        $this->assertTrue($this->cancellationService->isWithinCancellationWindow($transaction));
    }

    public function test_is_within_cancellation_window_returns_false_after_24_hours(): void
    {
        $transaction = $this->createCompletedTransaction([
            'created_at' => now()->subHours(25),
        ]);

        $this->assertFalse($this->cancellationService->isWithinCancellationWindow($transaction));
    }

    public function test_is_within_cancellation_window_boundary(): void
    {
        // Exactly at boundary (24 hours)
        $transaction = $this->createCompletedTransaction([
            'created_at' => now()->subHours(24),
        ]);

        $this->assertTrue($this->cancellationService->isWithinCancellationWindow($transaction));
    }

    public function test_cancellation_window_uses_config_value(): void
    {
        config(['cems.transaction_cancellation_window_hours' => 48]);

        $transaction = $this->createCompletedTransaction([
            'created_at' => now()->subHours(36),
        ]);

        $this->assertTrue($this->cancellationService->isWithinCancellationWindow($transaction));

        // Reset to default
        config(['cems.transaction_cancellation_window_hours' => 24]);
    }

    // =====================================================================
    // REFUND TRANSACTION CREATION TESTS
    // =====================================================================

    public function test_create_refund_transaction_creates_opposite_type(): void
    {
        $original = $this->createCompletedTransaction([
            'type' => TransactionType::Buy,
        ]);

        $refund = $this->cancellationService->createRefundTransaction($original);

        $this->assertEquals(TransactionType::Sell, $refund->type);
        $this->assertEquals($original->amount_local, $refund->amount_local);
        $this->assertEquals($original->amount_foreign, $refund->amount_foreign);
        $this->assertEquals($original->rate, $refund->rate);
        $this->assertEquals($original->id, $refund->original_transaction_id);
        $this->assertEquals(true, $refund->is_refund);
    }

    public function test_create_refund_transaction_for_sell_creates_buy(): void
    {
        $original = $this->createCompletedTransaction([
            'type' => TransactionType::Sell,
        ]);

        $refund = $this->cancellationService->createRefundTransaction($original);

        $this->assertEquals(TransactionType::Buy, $refund->type);
    }

    public function test_refund_transaction_is_completed(): void
    {
        $original = $this->createCompletedTransaction();

        $refund = $this->cancellationService->createRefundTransaction($original);

        $this->assertTrue($refund->status->isCompleted());
    }

    public function test_refund_transaction_preserves_customer_and_user(): void
    {
        $original = $this->createCompletedTransaction();

        $refund = $this->cancellationService->createRefundTransaction($original);

        $this->assertEquals($original->customer_id, $refund->customer_id);
        $this->assertEquals($original->user_id, $refund->user_id);
        $this->assertEquals($original->branch_id, $refund->branch_id);
        $this->assertEquals($original->till_id, $refund->till_id);
    }

    // =====================================================================
    // POSITION REVERSAL TESTS
    // =====================================================================

    public function test_reverse_positions_for_buy_transaction(): void
    {
        // Create position with initial balance
        CurrencyPosition::updateOrCreate(
            [
                'currency_code' => $this->currency->code,
                'till_id' => (string) $this->counter->id,
            ],
            [
                'balance' => '10000.0000',
                'avg_cost_rate' => '4.250000',
                'last_valuation_rate' => '4.250000',
            ]
        );

        $transaction = $this->createCompletedTransaction([
            'type' => TransactionType::Buy,
            'amount_foreign' => '1000.0000',
            'rate' => '4.250000',
        ]);

        // Get position before reversal
        $positionService = app(CurrencyPositionService::class);
        $positionBefore = $positionService->getPosition($this->currency->code, (string) $this->counter->id);
        $balanceBefore = $positionBefore->balance;

        $this->cancellationService->reversePositions($transaction);

        $positionAfter = $positionService->getPosition($this->currency->code, (string) $this->counter->id);
        $balanceAfter = $positionAfter->balance;

        // Buy reversal (Sell) should decrease position
        $expectedBalance = bcsub($balanceBefore, '1000.0000', 4);
        $this->assertEquals($expectedBalance, $balanceAfter);
    }

    public function test_reverse_positions_for_sell_transaction(): void
    {
        // Create position with initial balance
        CurrencyPosition::updateOrCreate(
            [
                'currency_code' => $this->currency->code,
                'till_id' => (string) $this->counter->id,
            ],
            [
                'balance' => '10000.0000',
                'avg_cost_rate' => '4.250000',
                'last_valuation_rate' => '4.250000',
            ]
        );

        $transaction = $this->createCompletedTransaction([
            'type' => TransactionType::Sell,
            'amount_foreign' => '1000.0000',
            'rate' => '4.250000',
        ]);

        // Get position before reversal
        $positionService = app(CurrencyPositionService::class);
        $positionBefore = $positionService->getPosition($this->currency->code, (string) $this->counter->id);
        $balanceBefore = $positionBefore->balance;

        $this->cancellationService->reversePositions($transaction);

        $positionAfter = $positionService->getPosition($this->currency->code, (string) $this->counter->id);
        $balanceAfter = $positionAfter->balance;

        // Sell reversal (Buy) should increase position
        $expectedBalance = bcadd($balanceBefore, '1000.0000', 4);
        $this->assertEquals($expectedBalance, $balanceAfter);
    }

    // =====================================================================
    // STATE HISTORY TESTS
    // =====================================================================

    public function test_record_state_history_updates_transaction(): void
    {
        $transaction = $this->createCompletedTransaction();

        $this->cancellationService->recordStateHistory(
            $transaction,
            'Completed',
            'Reversed',
            [
                'reason' => 'Test reversal',
                'user_id' => $this->manager->id,
            ]
        );

        $transaction->refresh();
        $history = $transaction->transition_history;

        $this->assertNotNull($history);
        $this->assertIsArray($history);
        $lastEntry = end($history);
        $this->assertEquals('Completed', $lastEntry['from']);
        $this->assertEquals('Reversed', $lastEntry['to']);
        $this->assertEquals('Test reversal', $lastEntry['reason']);
        $this->assertEquals($this->manager->id, $lastEntry['user_id']);
    }

    // =====================================================================
    // USER PERMISSION TESTS
    // =====================================================================

    public function test_manager_can_cancel_returns_true(): void
    {
        $this->assertTrue($this->cancellationService->canUserCancel($this->manager));
    }

    public function test_teller_can_cancel_returns_false(): void
    {
        $this->assertFalse($this->cancellationService->canUserCancel($this->teller));
    }

    public function test_manager_can_reverse_any_transaction(): void
    {
        $otherTeller = User::factory()->create([
            'branch_id' => $this->branch->id,
            'role' => UserRole::Teller,
        ]);

        $transaction = $this->createCompletedTransaction([
            'user_id' => $otherTeller->id,
        ]);

        $this->assertTrue($this->cancellationService->canUserReverse($this->manager, $transaction));
    }

    public function test_teller_can_reverse_own_transaction(): void
    {
        $transaction = $this->createCompletedTransaction([
            'user_id' => $this->teller->id,
        ]);

        $this->assertTrue($this->cancellationService->canUserReverse($this->teller, $transaction));
    }

    public function test_can_user_reverse_returns_false_for_others_transaction(): void
    {
        $otherTeller = User::factory()->create([
            'branch_id' => $this->branch->id,
            'role' => UserRole::Teller,
        ]);

        $transaction = $this->createCompletedTransaction([
            'user_id' => $otherTeller->id,
        ]);

        $this->assertFalse($this->cancellationService->canUserReverse($this->teller, $transaction));
    }

    // =====================================================================
    // FULL WORKFLOW TESTS
    // =====================================================================

    public function test_full_cancellation_workflow(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'status' => TransactionStatus::Approved,
        ]);

        // Request cancellation
        $result = $this->cancellationService->requestCancellation(
            $transaction,
            $this->manager,
            'Customer cancelled'
        );

        $this->assertTrue($result);

        $transaction->refresh();
        $this->assertTrue($transaction->status->isCancelled());
        $this->assertEquals('Customer cancelled', $transaction->cancellation_reason);
        $this->assertNotNull($transaction->cancelled_at);
        $this->assertEquals($this->manager->id, $transaction->cancelled_by);

        // Verify state history was recorded
        $history = $transaction->transition_history;
        $this->assertNotEmpty($history);
        $lastEntry = end($history);
        $this->assertEquals('Approved', $lastEntry['from']);
        $this->assertEquals('Cancelled', $lastEntry['to']);
    }

    public function test_full_reversal_workflow(): void
    {
        $transaction = $this->createCompletedTransaction([
            'type' => TransactionType::Buy,
            'amount_local' => '4250.00',
            'amount_foreign' => '1000.0000',
            'rate' => '4.250000',
            'created_at' => now()->subHours(2),
        ]);

        // Create original journal entry
        JournalEntry::create([
            'entry_date' => $transaction->created_at->toDateString(),
            'reference_type' => 'Transaction',
            'reference_id' => $transaction->id,
            'description' => 'Original transaction',
            'status' => 'Posted',
            'posted_by' => $this->teller->id,
            'posted_at' => now(),
        ]);

        // Request reversal
        $result = $this->cancellationService->requestReversal(
            $transaction,
            $this->manager,
            'Customer dispute'
        );

        $this->assertTrue($result);

        $transaction->refresh();
        $this->assertTrue($transaction->status->isReversed());
        $this->assertEquals('Customer dispute', $transaction->reversal_reason);

        // Verify refund transaction was created
        $refund = Transaction::where('original_transaction_id', $transaction->id)->first();
        $this->assertNotNull($refund);
        $this->assertEquals(TransactionType::Sell, $refund->type);
        $this->assertEquals($transaction->amount_local, $refund->amount_local);
        $this->assertEquals(true, $refund->is_refund);

        // Verify state history was recorded
        $history = $transaction->transition_history;
        $this->assertNotEmpty($history);
        $lastEntry = end($history);
        $this->assertEquals('Completed', $lastEntry['from']);
        $this->assertEquals('Reversed', $lastEntry['to']);
    }

    // =====================================================================
    // CONFIGURATION TESTS
    // =====================================================================

    public function test_get_cancellation_window_hours_returns_default(): void
    {
        $this->assertEquals(24, $this->cancellationService->getCancellationWindowHours());
    }

    public function test_get_cancellation_window_hours_returns_config_value(): void
    {
        config(['cems.transaction_cancellation_window_hours' => 48]);

        $this->assertEquals(48, $this->cancellationService->getCancellationWindowHours());

        // Reset to default
        config(['cems.transaction_cancellation_window_hours' => 24]);
    }
}
