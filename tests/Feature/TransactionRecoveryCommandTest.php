<?php

namespace Tests\Feature;

use App\Services\Transaction\TransactionRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionRecoveryCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function recover_command_delegates_to_recovery_service(): void
    {
        $recoveryService = Mockery::mock(TransactionRecoveryService::class);
        $recoveryService->shouldReceive('processPendingRecoveries')
            ->once()
            ->andReturn([
                'total' => 4,
                'retried' => 2,
                'moved_to_dlq' => 1,
                'not_ready' => 1,
            ]);

        $this->app->instance(TransactionRecoveryService::class, $recoveryService);

        $this->artisan('transactions:recover')
            ->assertSuccessful();

        $recoveryService->shouldHaveReceived('processPendingRecoveries')->once();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
