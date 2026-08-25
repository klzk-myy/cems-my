<?php

namespace Tests\Feature;

use App\Enums\SystemAlertLevel;
use App\Models\SystemAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemAlertsPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Dashboard widget data is cached per scope; start clean.
        Cache::flush();
    }

    private function createAlert(string $level = 'critical', string $message = 'Disk space critical'): SystemAlert
    {
        return SystemAlert::create([
            'level' => $level,
            'message' => $message,
            'source' => 'monitor',
        ]);
    }

    #[Test]
    public function admin_can_view_system_alerts_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->createAlert(SystemAlertLevel::Critical->value, 'Disk full');
        $this->createAlert(SystemAlertLevel::Info->value, 'Daily summary generated');

        $this->actingAs($admin)
            ->get(route('system.alerts.index'))
            ->assertOk()
            ->assertSee('System Alerts')
            ->assertSee('Disk full')
            ->assertSee('Daily summary generated');
    }

    #[Test]
    public function admin_can_acknowledge_alert(): void
    {
        $admin = User::factory()->admin()->create();
        $alert = $this->createAlert();

        $this->actingAs($admin)
            ->from(route('system.alerts.index'))
            ->post(route('system.alerts.acknowledge', $alert))
            ->assertRedirect(route('system.alerts.index'))
            ->assertSessionHas('success', 'Alert acknowledged.');

        $alert->refresh();
        $this->assertNotNull($alert->acknowledged_at);
        $this->assertEquals($admin->id, $alert->acknowledged_by);
    }

    #[Test]
    public function acknowledging_an_alert_twice_is_a_noop(): void
    {
        $admin = User::factory()->admin()->create();
        $alert = $this->createAlert();
        $alert->acknowledge($admin->id);

        $this->actingAs($admin)
            ->from(route('system.alerts.index'))
            ->post(route('system.alerts.acknowledge', $alert))
            ->assertRedirect(route('system.alerts.index'))
            ->assertSessionHas('info', 'Alert was already acknowledged.');

        $this->assertNotNull($alert->fresh()->acknowledged_at);
    }

    #[Test]
    public function email_link_shows_confirmation_page_without_acknowledging(): void
    {
        // The emailed acknowledge URL is a GET landing page; the mutation is
        // POST-only (CSRF-protected), so visiting the link must not change state.
        $admin = User::factory()->admin()->create();
        $alert = $this->createAlert();

        $this->actingAs($admin)
            ->get(route('system.alerts.acknowledge.show', $alert))
            ->assertOk()
            ->assertSee(route('system.alerts.acknowledge', $alert));

        $this->assertNull($alert->fresh()->acknowledged_at);
    }

    #[Test]
    public function acknowledging_an_alert_invalidates_the_dashboard_widget_cache(): void
    {
        // The dashboard widget caches recent unacknowledged alerts for 60s; an
        // acknowledge action must drop that cache so the alert disappears from
        // the dashboard immediately instead of lingering as stale.
        $admin = User::factory()->admin()->create();
        $alert = $this->createAlert(SystemAlertLevel::Critical->value, 'Cache stale alert');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('Cache stale alert');

        $this->actingAs($admin)
            ->post(route('system.alerts.acknowledge', $alert));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertDontSee('Cache stale alert');
    }

    #[Test]
    public function non_admins_cannot_access_the_page(): void
    {
        $teller = User::factory()->teller()->create();
        $manager = User::factory()->manager()->create();
        $alert = $this->createAlert();

        foreach ([$teller, $manager] as $user) {
            $this->actingAs($user)
                ->get(route('system.alerts.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('system.alerts.acknowledge', $alert))
                ->assertForbidden();
        }
    }

    #[Test]
    public function level_filter_shows_only_matching_alerts(): void
    {
        $admin = User::factory()->admin()->create();

        $this->createAlert(SystemAlertLevel::Critical->value, 'Critical alert only');
        $this->createAlert(SystemAlertLevel::Info->value, 'Info alert only');

        $this->actingAs($admin)
            ->get(route('system.alerts.index', ['level' => 'critical']))
            ->assertOk()
            ->assertSee('Critical alert only')
            ->assertDontSee('Info alert only');
    }

    #[Test]
    public function status_filter_separates_acknowledged_alerts(): void
    {
        $admin = User::factory()->admin()->create();

        $open = $this->createAlert(SystemAlertLevel::Warning->value, 'Open warning');
        $done = $this->createAlert(SystemAlertLevel::Info->value, 'Acknowledged info');
        $done->acknowledge($admin->id);

        $this->actingAs($admin)
            ->get(route('system.alerts.index', ['status' => 'unacknowledged']))
            ->assertOk()
            ->assertSee('Open warning')
            ->assertDontSee('Acknowledged info');

        $this->actingAs($admin)
            ->get(route('system.alerts.index', ['status' => 'acknowledged']))
            ->assertOk()
            ->assertSee('Acknowledged info')
            ->assertDontSee('Open warning');
    }

    #[Test]
    public function dashboard_system_alerts_card_links_to_the_page(): void
    {
        $admin = User::factory()->admin()->create();
        $alert = $this->createAlert(SystemAlertLevel::Critical->value, 'Dashboard alert message');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('View all alerts')
            ->assertSee('/system/alerts/'.$alert->id.'/acknowledge')
            ->assertSee('Dashboard alert message');
    }
}
