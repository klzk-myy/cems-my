<?php

namespace Tests\Feature;

use App\Enums\SystemAlertLevel;
use App\Enums\TransactionStatus;
use App\Models\SystemAlert;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\DlqTransactionAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlertDlqTransactionsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The command tracks already-alerted IDs in the cache; start clean.
        Cache::flush();
    }

    private function dlqTransaction(): Transaction
    {
        $transaction = Transaction::factory()->create([
            'status' => TransactionStatus::Failed,
        ]);

        $transaction->is_dlq = true;
        $transaction->save();

        return $transaction;
    }

    #[Test]
    public function command_alerts_admins_about_new_dlq_transactions(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $transaction = $this->dlqTransaction();

        $this->artisan('transactions:dlq-alert')
            ->expectsOutputToContain('Alerted 1 admin(s) about 1 new DLQ transaction(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('system_alerts', [
            'level' => SystemAlertLevel::Warning->value,
            'source' => 'transaction_dlq',
        ]);

        Notification::assertSentTo(
            [$admin],
            DlqTransactionAlertNotification::class,
            function (DlqTransactionAlertNotification $notification) use ($transaction) {
                return $notification->transactions->pluck('id')->contains($transaction->id);
            }
        );
    }

    #[Test]
    public function command_does_not_re_alert_same_dlq_transactions(): void
    {
        Notification::fake();

        User::factory()->admin()->create();
        $this->dlqTransaction();

        $this->artisan('transactions:dlq-alert')->assertSuccessful();

        $this->artisan('transactions:dlq-alert')
            ->expectsOutputToContain('already alerted')
            ->assertSuccessful();

        Notification::assertSentTimes(DlqTransactionAlertNotification::class, 1);

        // Exactly one warning alert was created (not one per sweep).
        $this->assertSame(
            1,
            SystemAlert::where('source', 'transaction_dlq')->count()
        );
    }

    #[Test]
    public function command_clears_alert_state_when_queue_is_drained(): void
    {
        Notification::fake();

        User::factory()->admin()->create();
        $transaction = $this->dlqTransaction();

        $this->artisan('transactions:dlq-alert')->assertSuccessful();

        // Resolve the stuck transaction: it leaves the DLQ.
        $transaction->is_dlq = false;
        $transaction->save();

        $this->artisan('transactions:dlq-alert')
            ->expectsOutputToContain('No transactions in the dead letter queue.')
            ->assertSuccessful();

        // A brand-new DLQ entry after a drain must re-alert (state was cleared).
        $this->dlqTransaction();

        $this->artisan('transactions:dlq-alert')->assertSuccessful();

        Notification::assertSentTimes(DlqTransactionAlertNotification::class, 2);
    }

    #[Test]
    public function dry_run_reports_without_notifying(): void
    {
        Notification::fake();

        User::factory()->admin()->create();
        $this->dlqTransaction();

        $this->artisan('transactions:dlq-alert', ['--dry-run' => true])
            ->expectsOutputToContain('[dry-run]')
            ->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertSame(0, SystemAlert::count());
    }
}
