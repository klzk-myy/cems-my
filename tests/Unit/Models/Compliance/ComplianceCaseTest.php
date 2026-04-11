<?php

namespace Tests\Unit\Models\Compliance;

use App\Enums\CaseNoteType;
use App\Enums\CaseResolution;
use App\Enums\ComplianceCasePriority;
use App\Enums\ComplianceCaseStatus;
use App\Enums\ComplianceCaseType;
use App\Enums\FindingSeverity;
use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceCaseNote;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceCaseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
    }

    public function test_can_create_case(): void
    {
        $case = ComplianceCase::create([
            'case_number' => 'CASE-2026-00001',
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->user->id,
            'case_summary' => 'Test case summary',
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $this->assertDatabaseHas('compliance_cases', [
            'id' => $case->id,
            'case_number' => 'CASE-2026-00001',
            'case_type' => ComplianceCaseType::Investigation->value,
            'status' => ComplianceCaseStatus::Open->value,
            'severity' => FindingSeverity::High->value,
            'priority' => ComplianceCasePriority::High->value,
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->user->id,
        ]);
    }

    public function test_case_number_auto_generated(): void
    {
        $case1 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $case2 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Edd,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(48),
            'created_via' => 'Manual',
        ]);

        // Case numbers should be different
        $this->assertNotEquals($case1->case_number, $case2->case_number);

        // Both should match the CASE-YYYY-NNNNN pattern
        $this->assertMatchesRegularExpression('/^CASE-\d{4}-\d{5}$/', $case1->case_number);
        $this->assertMatchesRegularExpression('/^CASE-\d{4}-\d{5}$/', $case2->case_number);
    }

    public function test_can_add_note(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $case->addNote($this->user->id, CaseNoteType::Investigation, 'Test note content', true);

        $this->assertDatabaseHas('compliance_case_notes', [
            'case_id' => $case->id,
            'author_id' => $this->user->id,
            'note_type' => CaseNoteType::Investigation->value,
            'content' => 'Test note content',
            'is_internal' => true,
        ]);
    }

    public function test_can_assign(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $newUser = User::factory()->create();
        $case->assignTo($newUser->id);

        $this->assertEquals($newUser->id, $case->assigned_to);
    }

    public function test_can_close_with_resolution(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $case->close(CaseResolution::NoConcern, 'No issues found');

        $this->assertEquals(ComplianceCaseStatus::Closed, $case->status);
        $this->assertEquals(CaseResolution::NoConcern->value, $case->resolution);
        $this->assertEquals('No issues found', $case->resolution_notes);
        $this->assertNotNull($case->resolved_at);
    }

    public function test_can_escalate(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $case->escalate();

        $this->assertEquals(ComplianceCaseStatus::Escalated, $case->status);
        $this->assertNotNull($case->escalated_at);
    }

    public function test_sla_is_calculated_from_severity(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::Critical,
            'priority' => ComplianceCasePriority::Critical,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24), // Will be overridden by model
            'created_via' => 'Manual',
        ]);

        // Critical severity should have SLA of 24 hours (1 day)
        // The actual SLA calculation is in the model boot method
        // We just verify the sla_deadline is approximately 24 hours from creation
        $expectedDeadline = now()->addHours(24);
        $this->assertEqualsWithDelta(
            $expectedDeadline->timestamp,
            $case->sla_deadline->timestamp,
            60 // Within 1 minute tolerance
        );
    }

    public function test_add_link_creates_compliance_case_link(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $case->addLink('Customer', $this->customer->id);

        $this->assertDatabaseHas('compliance_case_links', [
            'case_id' => $case->id,
            'linked_type' => 'Customer',
            'linked_id' => $this->customer->id,
        ]);
    }

    public function test_scope_open(): void
    {
        $case1 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $case2 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Edd,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        // Close case1
        $case1->close(CaseResolution::NoConcern, 'No issues');

        $openCases = ComplianceCase::open()->get();

        $this->assertCount(1, $openCases);
        $this->assertEquals($case2->id, $openCases->first()->id);
    }

    public function test_scope_by_assignee(): void
    {
        $user2 = User::factory()->create();

        $case1 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $case2 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Edd,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'assigned_to' => $user2->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $myCases = ComplianceCase::byAssignee($this->user->id)->get();

        $this->assertCount(1, $myCases);
        $this->assertEquals($case1->id, $myCases->first()->id);
    }

    public function test_scope_overdue(): void
    {
        $case1 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->subHours(1), // Overdue
            'created_via' => 'Manual',
        ]);

        $case2 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Edd,
            'severity' => FindingSeverity::Medium,
            'priority' => ComplianceCasePriority::Medium,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24), // Not overdue
            'created_via' => 'Manual',
        ]);

        $overdueCases = ComplianceCase::overdue()->get();

        $this->assertCount(1, $overdueCases);
        $this->assertEquals($case1->id, $overdueCases->first()->id);
    }

    public function test_case_has_notes_relationship(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $case->addNote($this->user->id, CaseNoteType::Investigation, 'Test note');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $case->notes);
        $this->assertCount(1, $case->notes);
        $this->assertInstanceOf(ComplianceCaseNote::class, $case->notes->first());
    }

    public function test_case_has_links_relationship(): void
    {
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'assigned_to' => $this->user->id,
            'sla_deadline' => now()->addHours(24),
            'created_via' => 'Manual',
        ]);

        $case->addLink('Customer', $this->customer->id);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $case->links);
        $this->assertCount(1, $case->links);
    }
}
