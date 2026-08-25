<?php

namespace Tests\Unit\Transaction;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\Transaction\TransactionErrorHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionErrorHandlerTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function fresh_error_begins_with_zero_retries(): void
    {
        $transaction = Transaction::factory()->create(['status' => TransactionStatus::Processing]);
        $handler = new TransactionErrorHandler;

        $this->assertSame(0, $handler->getRetryCount($transaction));
    }

    #[Test]
    public function retries_are_limited_to_max_retries_then_move_to_dlq(): void
    {
        $transaction = Transaction::factory()->create(['status' => TransactionStatus::Processing]);
        $handler = new TransactionErrorHandler;

        // Attempts 1-3 schedule a retry (retry count is carried forward each time)
        $this->assertTrue($handler->handleProcessingError($transaction, 'processing_error', 'failure 1'));
        $this->assertTrue($handler->handleProcessingError($transaction, 'processing_error', 'failure 2'));
        $this->assertTrue($handler->handleProcessingError($transaction, 'processing_error', 'failure 3'));

        $this->assertSame(3, $handler->getRetryCount($transaction->refresh()));

        // Fourth failure: max retries reached - no further retries and DLQ is due
        $this->assertFalse($handler->handleProcessingError($transaction, 'processing_error', 'failure 4'));
        $this->assertTrue($handler->shouldMoveToDLQ($transaction->refresh()));
    }
}
