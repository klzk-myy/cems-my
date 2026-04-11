<?php

namespace Tests\Unit\Services\Compliance;

use App\Enums\ComplianceCaseStatus;
use App\Enums\EddStatus;
use App\Enums\FindingSeverity;
use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceFinding;
use App\Models\Compliance\CustomerRiskProfile;
use App\Models\Customer;
use App\Models\EnhancedDiligenceRecord;
use App\Models\ReportGenerated;
use App\Models\StrReport;
use App\Models\User;
use App\Services\Compliance\ComplianceReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ComplianceReportingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComplianceReportingService;
    }

    /**
     * Test dashboard KPIs return correct case summary counts.
     */
    public function test_dashboard_kpis_returns_case_summary(): void
    {
        $user = User::factory()->create();

        // Create cases with different statuses using create() to ensure they're in the database
        $openCase1 = ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Open,
            'assigned_to' => $user->id,
        ]);
        $openCase2 = ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Open,
            'assigned_to' => $user->id,
        ]);
        $openCase3 = ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Open,
            'assigned_to' => $user->id,
        ]);
        $underReviewCase = ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::UnderReview,
            'assigned_to' => $user->id,
        ]);
        $escalatedCase = ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Escalated,
            'assigned_to' => $user->id,
        ]);
        $closedCase = ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Closed,
            'assigned_to' => $user->id,
        ]);

        $kpis = $this->service->getDashboardKpis();

        // Debug: check actual counts
        $totalCases = ComplianceCase::count();
        $openCount = ComplianceCase::where('status', ComplianceCaseStatus::Open->value)->count();

        $this->assertEquals(6, $totalCases, 'Should have 6 total cases');
        $this->assertEquals(3, $openCount, 'Should have 3 open cases');
        $this->assertEquals(3, $kpis['case_summary']['open']);
        $this->assertEquals(1, $kpis['case_summary']['under_review']);
        $this->assertEquals(1, $kpis['case_summary']['escalated']);
        $this->assertEquals(1, $kpis['case_summary']['closed']);
    }

    /**
     * Test dashboard KPIs return correct STR status counts.
     */
    public function test_dashboard_kpis_returns_str_status(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        // Create STRs with different statuses using string values directly
        // to avoid any enum casting issues

        // STR-1: Draft, future deadline
        StrReport::create([
            'str_no' => 'STR-20260401-0001',
            'customer_id' => $customer->id,
            'reason' => 'Test STR 1',
            'transaction_ids' => json_encode([]),
            'status' => 'draft',
            'created_by' => $user->id,
            'filing_deadline' => now()->addWeekdays(3),
        ]);
        // STR-2: PendingApproval, future deadline
        StrReport::create([
            'str_no' => 'STR-20260401-0002',
            'customer_id' => $customer->id,
            'reason' => 'Test STR 2',
            'transaction_ids' => json_encode([]),
            'status' => 'pending_approval',
            'created_by' => $user->id,
            'filing_deadline' => now()->addWeekdays(3),
        ]);
        // STR-3: Submitted, deadline in future (filed)
        StrReport::create([
            'str_no' => 'STR-20260401-0003',
            'customer_id' => $customer->id,
            'reason' => 'Test STR 3',
            'transaction_ids' => json_encode([]),
            'status' => 'submitted',
            'created_by' => $user->id,
            'submitted_at' => now()->subDays(1),
            'filing_deadline' => now()->addDays(5),
        ]);
        // STR-4: Acknowledged, deadline in past (filed and acknowledged)
        StrReport::create([
            'str_no' => 'STR-20260401-0004',
            'customer_id' => $customer->id,
            'reason' => 'Test STR 4',
            'transaction_ids' => json_encode([]),
            'status' => 'acknowledged',
            'created_by' => $user->id,
            'submitted_at' => now()->subDays(5),
            'bnm_reference' => 'BNM-123',
            'filing_deadline' => now()->subDays(10),
        ]);

        // Debug: check actual STR counts
        $totalStrs = StrReport::count();
        $pendingStrs = StrReport::whereIn('status', ['draft', 'pending_review', 'pending_approval'])->count();

        $kpis = $this->service->getDashboardKpis();

        $this->assertEquals(4, $totalStrs, 'Should have 4 STRs');
        // 2 pending: Draft + PendingApproval
        $this->assertEquals(2, $pendingStrs, 'Should have 2 pending STRs');
        $this->assertEquals(2, $kpis['str_status']['pending']);
        // Due today should be 0 (no deadlines set to today)
        $this->assertEquals(0, $kpis['str_status']['due_today']);
        // Overdue should be 0 (no unfiled STRs with past deadlines)
        $this->assertEquals(0, $kpis['str_status']['overdue']);
        // Filed should be 2 (submitted + acknowledged)
        $this->assertEquals(2, $kpis['str_status']['filed']);
    }

    /**
     * Test dashboard KPIs return correct EDD status counts.
     */
    public function test_dashboard_kpis_returns_edd_status(): void
    {
        // Create EDD records with different statuses
        // Note: EnhancedDiligenceRecord doesn't have expiry_date column
        // We test based on status field only
        EnhancedDiligenceRecord::factory()->create([
            'status' => EddStatus::Approved,
        ]);
        EnhancedDiligenceRecord::factory()->create([
            'status' => EddStatus::PendingReview,
        ]);
        EnhancedDiligenceRecord::factory()->create([
            'status' => EddStatus::PendingQuestionnaire,
        ]);
        EnhancedDiligenceRecord::factory()->create([
            'status' => EddStatus::PendingQuestionnaire,
        ]);
        EnhancedDiligenceRecord::factory()->create([
            'status' => EddStatus::Expired,
        ]);

        $kpis = $this->service->getDashboardKpis();

        // Active = not closed (not Expired, Rejected, or Approved)
        $this->assertEquals(3, $kpis['edd_status']['active']);
        // Expired count based on status
        $this->assertEquals(1, $kpis['edd_status']['expired']);
    }

    /**
     * Test dashboard KPIs return findings by severity for last 7 days.
     */
    public function test_dashboard_kpis_returns_findings_by_severity(): void
    {
        // Create findings in last 7 days
        ComplianceFinding::factory()->create([
            'severity' => FindingSeverity::High,
            'generated_at' => now()->subDays(2),
        ]);
        ComplianceFinding::factory()->create([
            'severity' => FindingSeverity::High,
            'generated_at' => now()->subDays(3),
        ]);
        ComplianceFinding::factory()->create([
            'severity' => FindingSeverity::Medium,
            'generated_at' => now()->subDays(4),
        ]);
        // Create old finding (outside 7 days)
        ComplianceFinding::factory()->create([
            'severity' => FindingSeverity::Critical,
            'generated_at' => now()->subDays(10),
        ]);

        $kpis = $this->service->getDashboardKpis();

        $this->assertEquals(2, $kpis['open_findings_7_days']['High']);
        $this->assertEquals(1, $kpis['open_findings_7_days']['Medium']);
        $this->assertArrayNotHasKey('Critical', $kpis['open_findings_7_days']);
    }

    /**
     * Test dashboard KPIs return risk distribution across customer portfolio.
     */
    public function test_dashboard_kpis_returns_risk_distribution(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $customer3 = Customer::factory()->create();

        // Create risk profiles directly since factory doesn't exist
        CustomerRiskProfile::create([
            'customer_id' => $customer1->id,
            'risk_score' => 20,
            'risk_tier' => 'Low',
        ]);
        CustomerRiskProfile::create([
            'customer_id' => $customer2->id,
            'risk_score' => 45,
            'risk_tier' => 'Medium',
        ]);
        CustomerRiskProfile::create([
            'customer_id' => $customer3->id,
            'risk_score' => 70,
            'risk_tier' => 'High',
        ]);

        $kpis = $this->service->getDashboardKpis();

        $this->assertEquals(1, $kpis['risk_distribution']['Low']);
        $this->assertEquals(1, $kpis['risk_distribution']['Medium']);
        $this->assertEquals(1, $kpis['risk_distribution']['High']);
    }

    /**
     * Test case aging returns SLA metrics.
     */
    public function test_case_aging_returns_sla_metrics(): void
    {
        $user = User::factory()->create();

        // Create closed case with resolution time
        $case1 = ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Closed,
            'severity' => FindingSeverity::High,
            'assigned_to' => $user->id,
            'created_at' => now()->subDays(5),
            'resolved_at' => now()->subDays(2),
        ]);

        // Create open case past SLA
        $case2 = ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::Critical,
            'assigned_to' => $user->id,
            'created_at' => now()->subDays(3),
            'sla_deadline' => now()->subDays(1),
        ]);

        // Create open case within SLA
        $case3 = ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::Low,
            'assigned_to' => $user->id,
            'created_at' => now()->subHours(6),
            'sla_deadline' => now()->addHours(18),
        ]);

        $aging = $this->service->getCaseAging();

        $this->assertEquals(1, $aging['cases_breaching_sla']);
        $this->assertNotNull($aging['oldest_open_case']);
        $this->assertEquals($case2->id, $aging['oldest_open_case']['id']);
    }

    /**
     * Test BNM regulatory calendar calculates filing deadlines.
     */
    public function test_calendar_calculates_filing_deadlines(): void
    {
        $calendar = $this->service->getBnmCalendar();

        // Should have LCTR for current month
        $this->assertArrayHasKey('upcoming', $calendar);
        $this->assertNotEmpty($calendar['upcoming']);

        // Find LCTR deadline
        $lctr = collect($calendar['upcoming'])->firstWhere('type', 'LCTR');
        $this->assertNotNull($lctr);
        $this->assertEquals('LCTR', $lctr['type']);
        $this->assertEquals(7, $lctr['working_days_deadline']);
    }

    /**
     * Test calendar includes QLVR for current quarter.
     */
    public function test_calendar_includes_qlvr_for_current_quarter(): void
    {
        $calendar = $this->service->getBnmCalendar();

        $qlvr = collect($calendar['upcoming'])->firstWhere('type', 'QLVR');
        $this->assertNotNull($qlvr);
        $this->assertEquals('QLVR', $qlvr['type']);
        $this->assertEquals(10, $qlvr['working_days_deadline']);
    }

    /**
     * Test audit trail returns paginated compliance actions.
     */
    public function test_audit_trail_returns_paginated_results(): void
    {
        $user = User::factory()->create();

        // Create some cases that generate system logs
        $case = ComplianceCase::factory()->create([
            'assigned_to' => $user->id,
        ]);

        // Get the audit trail
        $trail = $this->service->getAuditTrail(['per_page' => 10]);

        $this->assertArrayHasKey('data', $trail);
        $this->assertArrayHasKey('current_page', $trail);
        $this->assertArrayHasKey('total', $trail);
    }

    /**
     * Test audit trail can be filtered by date range.
     */
    public function test_audit_trail_can_filter_by_date_range(): void
    {
        $user = User::factory()->create();

        ComplianceCase::factory()->create([
            'assigned_to' => $user->id,
            'created_at' => now()->subDays(5),
        ]);

        $from = now()->subDays(7)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $trail = $this->service->getAuditTrail([
            'from_date' => $from,
            'to_date' => $to,
        ]);

        $this->assertIsArray($trail['data']);
    }

    /**
     * Test auto-generated reports returns pending reports.
     */
    public function test_auto_generated_reports_returns_pending(): void
    {
        $user = User::factory()->create();

        // Create report generated records
        ReportGenerated::factory()->create([
            'report_type' => 'MSB2',
            'generated_by' => $user->id,
            'status' => 'Pending',
        ]);
        ReportGenerated::factory()->create([
            'report_type' => 'LCTR',
            'generated_by' => $user->id,
            'status' => 'Pending',
        ]);
        ReportGenerated::factory()->create([
            'report_type' => 'MSB2',
            'generated_by' => $user->id,
            'status' => 'Submitted',
        ]);

        $reports = $this->service->getAutoGeneratedReports();

        $this->assertEquals(2, $reports['pending_count']);
        $this->assertNotEmpty($reports['pending_reports']);
    }

    /**
     * Test case aging calculates average resolution time.
     */
    public function test_case_aging_calculates_avg_resolution_time(): void
    {
        $user = User::factory()->create();

        // Create multiple closed cases with known resolution times
        ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Closed,
            'severity' => FindingSeverity::Medium,
            'assigned_to' => $user->id,
            'created_at' => now()->subDays(10),
            'resolved_at' => now()->subDays(8), // 2 days to resolve
        ]);
        ComplianceCase::factory()->create([
            'status' => ComplianceCaseStatus::Closed,
            'severity' => FindingSeverity::Medium,
            'assigned_to' => $user->id,
            'created_at' => now()->subDays(20),
            'resolved_at' => now()->subDays(16), // 4 days to resolve
        ]);

        $aging = $this->service->getCaseAging();

        // Average should be 3 days (72 hours)
        $this->assertArrayHasKey('avg_resolution_time_hours', $aging);
        $this->assertEquals(72, $aging['avg_resolution_time_hours']);
    }
}
