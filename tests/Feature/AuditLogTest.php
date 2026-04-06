<?php

namespace Tests\Feature;

use App\Models\SystemLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $user;

    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->user = User::factory()->create(['role' => 'teller']);
        $this->auditService = app(AuditService::class);
    }

    public function test_audit_log_index_is_accessible_by_manager()
    {
        $this->actingAs($this->manager);

        $response = $this->get(route('audit.index'));

        $response->assertStatus(200);
        $response->assertViewHas('logs');
    }

    public function test_audit_log_index_is_not_accessible_by_regular_user()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('audit.index'));

        $response->assertStatus(403);
    }

    public function test_log_with_severity_creates_entry_with_correct_severity()
    {
        $log = $this->auditService->logWithSeverity('test_action', [
            'old_values' => ['name' => 'Old'],
            'new_values' => ['name' => 'New'],
        ], 'WARNING');

        $this->assertDatabaseHas('system_logs', [
            'action' => 'test_action',
            'severity' => 'WARNING',
        ]);

        $this->assertEquals('WARNING', $log->severity);
    }

    public function test_get_audit_trail_returns_filtered_results_by_date()
    {
        // Create logs on different dates
        SystemLog::create([
            'action' => 'old_action',
            'created_at' => now()->subDays(10),
        ]);

        SystemLog::create([
            'action' => 'recent_action',
            'created_at' => now(),
        ]);

        $result = $this->auditService->getAuditTrail([
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $logs = collect($result['logs']->items());

        $this->assertCount(1, $logs);
        $this->assertEquals('recent_action', $logs->first()->action);
    }

    public function test_get_audit_trail_returns_filtered_results_by_severity()
    {
        SystemLog::create([
            'action' => 'info_action',
            'severity' => 'INFO',
        ]);

        SystemLog::create([
            'action' => 'error_action',
            'severity' => 'ERROR',
        ]);

        SystemLog::create([
            'action' => 'critical_action',
            'severity' => 'CRITICAL',
        ]);

        $result = $this->auditService->getAuditTrail([
            'severity' => 'ERROR',
        ]);

        $logs = collect($result['logs']->items());

        $this->assertCount(1, $logs);
        $this->assertEquals('error_action', $logs->first()->action);
    }

    public function test_get_audit_trail_returns_filtered_results_by_user()
    {
        SystemLog::create([
            'action' => 'user_action',
            'user_id' => $this->user->id,
        ]);

        SystemLog::create([
            'action' => 'manager_action',
            'user_id' => $this->manager->id,
        ]);

        $result = $this->auditService->getAuditTrail([
            'user_id' => $this->manager->id,
        ]);

        $logs = collect($result['logs']->items());

        $this->assertCount(1, $logs);
        $this->assertEquals('manager_action', $logs->first()->action);
    }

    public function test_get_audit_trail_returns_filtered_results_by_action()
    {
        SystemLog::create([
            'action' => 'transaction_created',
        ]);

        SystemLog::create([
            'action' => 'transaction_updated',
        ]);

        SystemLog::create([
            'action' => 'user_login',
        ]);

        $result = $this->auditService->getAuditTrail([
            'action' => 'transaction',
        ]);

        $logs = collect($result['logs']->items());

        $this->assertCount(2, $logs);
    }

    public function test_audit_log_severity_scope_works_correctly()
    {
        SystemLog::create([
            'action' => 'info_action',
            'severity' => 'INFO',
        ]);

        SystemLog::create([
            'action' => 'warning_action',
            'severity' => 'WARNING',
        ]);

        SystemLog::create([
            'action' => 'error_action',
            'severity' => 'ERROR',
        ]);

        // Test severity filter
        $logs = SystemLog::severity('ERROR')->get();
        $this->assertCount(1, $logs);
        $this->assertEquals('error_action', $logs->first()->action);
    }

    public function test_system_log_scope_between_dates_works()
    {
        SystemLog::create([
            'action' => 'old_action',
            'created_at' => now()->subDays(10),
        ]);

        SystemLog::create([
            'action' => 'recent_action',
            'created_at' => now(),
        ]);

        $logs = SystemLog::betweenDates(
            now()->subDays(5)->toDateString(),
            now()->toDateString()
        )->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('recent_action', $logs->first()->action);
    }

    public function test_system_log_scope_action_works()
    {
        SystemLog::create(['action' => 'login']);
        SystemLog::create(['action' => 'logout']);
        SystemLog::create(['action' => 'transaction_create']);

        $logs = SystemLog::action('login')->get();

        $this->assertCount(1, $logs);
    }

    public function test_system_log_get_severity_color_returns_correct_color()
    {
        $critical = new SystemLog(['severity' => 'CRITICAL']);
        $error = new SystemLog(['severity' => 'ERROR']);
        $warning = new SystemLog(['severity' => 'WARNING']);
        $info = new SystemLog(['severity' => 'INFO']);

        $this->assertEquals('red', $critical->getSeverityColor());
        $this->assertEquals('orange', $error->getSeverityColor());
        $this->assertEquals('yellow', $warning->getSeverityColor());
        $this->assertEquals('blue', $info->getSeverityColor());
    }

    public function test_export_audit_log_returns_csv_data()
    {
        SystemLog::create([
            'action' => 'test_action',
            'severity' => 'INFO',
            'user_id' => $this->user->id,
        ]);

        $result = $this->auditService->exportAuditLog(
            now()->subDay()->toDateString(),
            now()->toDateString(),
            'CSV'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('callback', $result);
        $this->assertArrayHasKey('headers', $result);
    }

    public function test_log_with_severity_includes_session_id()
    {
        $log = $this->auditService->logWithSeverity('test_action', [], 'INFO');

        $this->assertNotNull($log->session_id);
    }

    public function test_audit_log_filters_can_be_combined()
    {
        SystemLog::create([
            'action' => 'transaction_created',
            'severity' => 'INFO',
            'user_id' => $this->manager->id,
            'created_at' => now(),
        ]);

        SystemLog::create([
            'action' => 'transaction_created',
            'severity' => 'ERROR',
            'user_id' => $this->manager->id,
            'created_at' => now(),
        ]);

        SystemLog::create([
            'action' => 'user_login',
            'severity' => 'INFO',
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $result = $this->auditService->getAuditTrail([
            'user_id' => $this->manager->id,
            'action' => 'transaction',
            'severity' => 'INFO',
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $logs = collect($result['logs']->items());

        $this->assertCount(1, $logs);
        $this->assertEquals('transaction_created', $logs->first()->action);
    }
}
