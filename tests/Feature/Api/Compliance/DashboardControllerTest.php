<?php

namespace Tests\Feature\Api\Compliance;

use App\Enums\ComplianceCaseStatus;
use App\Enums\EddStatus;
use App\Enums\FindingSeverity;
use App\Enums\UserRole;
use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceFinding;
use App\Models\Compliance\CustomerRiskProfile;
use App\Models\Customer;
use App\Models\EnhancedDiligenceRecord;
use App\Models\ReportGenerated;
use App\Models\StrReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $complianceUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->complianceUser = User::create([
            'username' => 'compliance1',
            'email' => 'compliance@cems.my',
            'password_hash' => Hash::make('Compliance@1234'),
            'role' => UserRole::ComplianceOfficer,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Test can get dashboard KPIs.
     */
    public function test_can_get_dashboard_kpis(): void
    {
        // Create some test data
        $customer = Customer::factory()->create();

        ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Open,
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);
        ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Closed,
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'case_summary',
                'str_status',
                'edd_status',
                'open_findings_7_days',
                'risk_distribution',
            ],
            'generated_at',
        ]);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test can get BNM regulatory calendar.
     */
    public function test_can_get_calendar(): void
    {
        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/calendar');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'upcoming',
            ],
        ]);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test calendar includes required report types.
     */
    public function test_calendar_includes_required_report_types(): void
    {
        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/calendar');

        $response->assertStatus(200);

        $upcoming = collect($response->json('data.upcoming'));
        $types = $upcoming->pluck('type')->toArray();

        $this->assertContains('LCTR', $types);
        $this->assertContains('LMCA', $types);
        $this->assertContains('QLVR', $types);
    }

    /**
     * Test can get case aging metrics.
     */
    public function test_can_get_case_aging(): void
    {
        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/case-aging');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'avg_resolution_time_hours',
                'cases_breaching_sla',
                'oldest_open_case',
            ],
        ]);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test can get audit trail.
     */
    public function test_can_get_audit_trail(): void
    {
        $customer = Customer::factory()->create();

        ComplianceCase::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/audit-trail');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
        ]);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test can filter audit trail by date range.
     */
    public function test_can_filter_audit_trail_by_date(): void
    {
        $customer = Customer::factory()->create();

        ComplianceCase::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
            'created_at' => now()->subDays(5),
        ]);

        $from = now()->subDays(7)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson("/api/compliance/audit-trail?from_date={$from}&to_date={$to}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test can export audit trail as CSV.
     */
    public function test_can_export_audit_trail_csv(): void
    {
        $customer = Customer::factory()->create();

        ComplianceCase::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->get('/api/compliance/audit-trail/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test can get auto-generated reports.
     */
    public function test_can_get_auto_reports(): void
    {
        ReportGenerated::factory()->create([
            'report_type' => 'MSB2',
            'generated_by' => $this->complianceUser->id,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/reports/auto');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'pending_count',
                'pending_reports',
                'recent_reports',
            ],
        ]);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test KPIs include correct case summary counts.
     */
    public function test_kpis_include_correct_case_counts(): void
    {
        $customer = Customer::factory()->create();

        // Create 2 Open cases
        ComplianceCase::factory()->count(2)->create([
            'status' => ComplianceCaseStatus::Open,
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);
        // Create 1 UnderReview case
        ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::UnderReview,
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);
        // Create 1 Closed case
        ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Closed,
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/dashboard');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(2, $data['case_summary']['open']);
        $this->assertEquals(1, $data['case_summary']['under_review']);
        $this->assertEquals(1, $data['case_summary']['closed']);
    }

    /**
     * Test KPIs include risk distribution.
     */
    public function test_kpis_include_risk_distribution(): void
    {
        $customer = Customer::factory()->create();

        CustomerRiskProfile::create([
            'customer_id' => $customer->id,
            'risk_score' => 20,
            'risk_tier' => 'Low',
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/dashboard');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertArrayHasKey('risk_distribution', $data);
        $this->assertEquals(1, $data['risk_distribution']['Low']);
    }

    /**
     * Test unauthenticated access is denied.
     */
    public function test_unauthenticated_access_denied(): void
    {
        $response = $this->getJson('/api/compliance/dashboard');

        $response->assertStatus(401);
    }

    /**
     * Test audit trail pagination parameters.
     */
    public function test_audit_trail_respects_per_page(): void
    {
        $customer = Customer::factory()->create();

        // Create 5 cases
        ComplianceCase::factory()->count(5)->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/audit-trail?per_page=2');

        $response->assertStatus(200);

        $pagination = $response->json('pagination');
        $this->assertEquals(2, $pagination['per_page']);
    }
}
