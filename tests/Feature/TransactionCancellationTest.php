<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Transaction\TransactionCancellationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionCancellationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function cancel_calls_request_cancellation()
    {
        $branch = Branch::factory()->create();
        $transaction = Transaction::factory()->for($branch)->create(['status' => TransactionStatus::Completed]);

        $cancellationService = \Mockery::mock(TransactionCancellationService::class);
        $cancellationService->shouldReceive('canCancel')
            ->once()
            ->andReturn(true);
        $cancellationService->shouldReceive('requestCancellation')
            ->once()
            ->with(
                \Mockery::on(function ($t) use ($transaction) {
                    return $t->id === $transaction->id;
                }),
                \Mockery::on(function ($u) {
                    return $u instanceof User;
                }),
                'Test cancellation reason'
            )
            ->andReturn(true);

        $this->app->instance(TransactionCancellationService::class, $cancellationService);

        $user = User::factory()->for($branch)->create(['role' => UserRole::Manager]);
        $response = $this->actingAs($user)
            ->post("/transactions/{$transaction->id}/cancel", [
                'cancellation_reason' => 'Test cancellation reason',
                'confirm_understanding' => true,
            ]);

        $response->assertRedirect();
    }
}
