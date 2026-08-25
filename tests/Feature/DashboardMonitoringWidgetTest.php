<?php

namespace Tests\Feature;

use App\Enums\SystemAlertLevel;
use App\Enums\TransactionStatus;
use App\Models\SystemAlert;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardMonitoringWidgetTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Dashboard stats are cached per scope; start clean so each test sees
        // freshly computed counts.
        Cache::flush();
    }

    #[Test]
    public function admin_sees_dlq_count_in_monitoring_widget(): void
    {
        $admin = User::factory()->admin()->create();

        $transaction = Transaction::factory()->create([
            'status' => TransactionStatus::Failed,
        ]);
        $transaction->is_dlq = true;
        $transaction->save();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dead Letter Queue')
            ->assertSee('Review queue')
            ->assertSeeInOrder(['Dead Letter Queue', '1']);
    }

    #[Test]
    public function admin_sees_system_alert_counts_and_recent_alerts(): void
    {
        $admin = User::factory()->admin()->create();

        SystemAlert::create([
            'level' => SystemAlertLevel::Critical->value,
            'message' => 'Disk space critical',
            'source' => 'test',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('System Alerts')
            ->assertSee('Disk space critical');
    }

    #[Test]
    public function non_admin_does_not_see_monitoring_widget(): void
    {
        $teller = User::factory()->teller()->create();

        $this->actingAs($teller)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Dead Letter Queue')
            ->assertDontSee('System Alerts');
    }

    #[Test]
    public function manager_does_not_see_monitoring_widget(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Dead Letter Queue')
            ->assertDontSee('System Alerts');
    }
}
