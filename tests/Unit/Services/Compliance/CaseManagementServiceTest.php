<?php

namespace Tests\Unit\Services\Compliance;

use App\Enums\CaseNoteType;
use App\Enums\CaseResolution;
use App\Enums\ComplianceCasePriority;
use App\Enums\ComplianceCaseStatus;
use App\Enums\ComplianceCaseType;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\FindingType;
use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceFinding;
use App\Models\Customer;
use App\Models\User;
use App\Services\Compliance\CaseManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private CaseManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CaseManagementService();
    }

    public function test_can_create_case_from_finding(): void
    {
        // Create a customer and user for the finding
        $customer = Customer::factory()->create();
        $assignedUser = User::factory()->create();

        // Create a compliance finding - subject is the customer
        $finding = ComplianceFinding::factory()->create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'status' => FindingStatus::New,
        ]);

        // Create the case from the finding
        $case = $this->service->createCaseFromFinding(
            $finding,
            ComplianceCaseType::Investigation,
            $assignedUser->id,
            'Test case summary'
        );

        // Assert case was created correctly
        $this->assertInstanceOf(ComplianceCase::class, $case);
        $this->assertEquals(ComplianceCaseType::Investigation, $case->case_type);
        $this->assertEquals(ComplianceCaseStatus::Open, $case->status);
        $this->assertEquals(FindingSeverity::High, $case->severity);
        $this->assertEquals(ComplianceCasePriority::High, $case->priority);
        $this->assertEquals($customer->id, $case->customer_id);
        $this->assertEquals($finding->id, $case->primary_finding_id);
        $this->assertEquals($assignedUser->id, $case->assigned_to);
        $this->assertEquals('Test case summary', $case->case_summary);
        $this->assertEquals('Automated', $case->created_via);
        $this->assertNotNull($case->sla_deadline);

        // Assert case number was auto-generated
        $this->assertStringStartsWith('CASE-' . now()->year . '-', $case->case_number);
    }

    public function test_finding_marked_case_created_when_case_created(): void
    {
        $customer = Customer::factory()->create();
        $assignedUser = User::factory()->create();

        $finding = ComplianceFinding::factory()->create([
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'status' => FindingStatus::New,
        ]);

        $this->assertEquals(FindingStatus::New, $finding->status);

        $this->service->createCaseFromFinding(
            $finding,
            ComplianceCaseType::Investigation,
            $assignedUser->id
        );

        // Refresh the finding from database
        $finding->refresh();

        // Assert finding status is now CaseCreated
        $this->assertEquals(FindingStatus::CaseCreated, $finding->status);
    }

    public function test_can_add_note_to_case(): void
    {
        $customer = Customer::factory()->create();
        $assignedUser = User::factory()->create();
        $author = User::factory()->create();

        // Create a case directly
        $case = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'customer_id' => $customer->id,
            'assigned_to' => $assignedUser->id,
            'case_summary' => 'Test case',
            'created_via' => 'Manual',
        ]);

        // Add a note via the service
        $note = $this->service->addNote(
            $case,
            $author->id,
            CaseNoteType::Investigation,
            'This is a test note content',
            true
        );

        // Assert note was created
        $this->assertInstanceOf(\App\Models\Compliance\ComplianceCaseNote::class, $note);
        $this->assertEquals($case->id, $note->case_id);
        $this->assertEquals($author->id, $note->author_id);
        $this->assertEquals(CaseNoteType::Investigation, $note->note_type);
        $this->assertEquals('This is a test note content', $note->content);
        $this->assertTrue($note->is_internal);

        // Assert note exists in database
        $this->assertDatabaseHas('compliance_case_notes', [
            'case_id' => $case->id,
            'author_id' => $author->id,
            'note_type' => CaseNoteType::Investigation->value,
            'content' => 'This is a test note content',
            'is_internal' => true,
        ]);
    }

    public function test_case_number_unique_and_auto_incrementing(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        // Create first case directly
        $case1 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'customer_id' => $customer->id,
            'assigned_to' => $user->id,
            'case_summary' => 'Test case 1',
            'created_via' => 'Manual',
        ]);

        // Create second case directly
        $case2 = ComplianceCase::create([
            'case_type' => ComplianceCaseType::Investigation,
            'status' => ComplianceCaseStatus::Open,
            'severity' => FindingSeverity::High,
            'priority' => ComplianceCasePriority::High,
            'customer_id' => $customer->id,
            'assigned_to' => $user->id,
            'case_summary' => 'Test case 2',
            'created_via' => 'Manual',
        ]);

        // Assert case numbers are different
        $this->assertNotEquals($case1->case_number, $case2->case_number);

        // Assert case numbers are sequential
        $case1Number = (int) substr($case1->case_number, -5);
        $case2Number = (int) substr($case2->case_number, -5);
        $this->assertEquals(1, $case2Number - $case1Number);
    }
}
