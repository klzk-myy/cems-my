<?php

namespace Tests\Unit;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\FindingType;
use App\Models\Compliance\ComplianceFinding;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceFindingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->transaction = Transaction::factory()->create();
    }

    public function test_can_create_finding(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'details' => ['velocity_24h' => 60000, 'threshold' => 50000],
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $this->assertDatabaseHas('compliance_findings', [
            'id' => $finding->id,
            'finding_type' => FindingType::VelocityExceeded->value,
            'severity' => FindingSeverity::High->value,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New->value,
        ]);
    }

    public function test_can_be_dismissed(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $finding->dismiss('False positive - customer verified');

        $this->assertEquals(FindingStatus::Dismissed, $finding->status);
    }

    public function test_dismiss_throws_exception_for_invalid_status(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::CaseCreated,
            'generated_at' => now(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Finding cannot be dismissed in Case Created status');

        $finding->dismiss('Should fail');
    }

    public function test_can_transition_to_case_created(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::SanctionMatch,
            'severity' => FindingSeverity::Critical,
            'subject_type' => 'Transaction',
            'subject_id' => $this->transaction->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $finding->markCaseCreated();

        $this->assertEquals(FindingStatus::CaseCreated, $finding->status);
    }

    public function test_mark_case_created_throws_exception_for_invalid_status(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::SanctionMatch,
            'severity' => FindingSeverity::Critical,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::Dismissed,
            'generated_at' => now(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Case cannot be created from finding in Dismissed status');

        $finding->markCaseCreated();
    }

    public function test_is_new_returns_true_for_new_finding(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $this->assertTrue($finding->isNew());
    }

    public function test_is_new_returns_false_for_non_new_finding(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::Reviewed,
            'generated_at' => now(),
        ]);

        $this->assertFalse($finding->isNew());
    }

    public function test_is_critical_returns_true_for_critical_severity(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::SanctionMatch,
            'severity' => FindingSeverity::Critical,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $this->assertTrue($finding->isCritical());
    }

    public function test_is_critical_returns_false_for_non_critical_severity(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $this->assertFalse($finding->isCritical());
    }

    public function test_subject_returns_polymorphic_relationship(): void
    {
        $finding = ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $this->assertInstanceOf(Customer::class, $finding->subject);
        $this->assertEquals($this->customer->id, $finding->subject->id);
    }

    public function test_scope_with_status(): void
    {
        ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        ComplianceFinding::create([
            'finding_type' => FindingType::StructuringPattern,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::Dismissed,
            'generated_at' => now(),
        ]);

        $newFindings = ComplianceFinding::withStatus(FindingStatus::New)->get();

        $this->assertCount(1, $newFindings);
        $this->assertEquals(FindingStatus::New, $newFindings->first()->status);
    }

    public function test_scope_with_severity(): void
    {
        ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        ComplianceFinding::create([
            'finding_type' => FindingType::SanctionMatch,
            'severity' => FindingSeverity::Critical,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $criticalFindings = ComplianceFinding::withSeverity(FindingSeverity::Critical)->get();

        $this->assertCount(1, $criticalFindings);
        $this->assertEquals(FindingSeverity::Critical, $criticalFindings->first()->severity);
    }

    public function test_scope_new(): void
    {
        ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        ComplianceFinding::create([
            'finding_type' => FindingType::StructuringPattern,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::Dismissed,
            'generated_at' => now(),
        ]);

        $newFindings = ComplianceFinding::new()->get();

        $this->assertCount(1, $newFindings);
    }

    public function test_scope_of_type(): void
    {
        ComplianceFinding::create([
            'finding_type' => FindingType::VelocityExceeded,
            'severity' => FindingSeverity::High,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        ComplianceFinding::create([
            'finding_type' => FindingType::SanctionMatch,
            'severity' => FindingSeverity::Critical,
            'subject_type' => 'Customer',
            'subject_id' => $this->customer->id,
            'status' => FindingStatus::New,
            'generated_at' => now(),
        ]);

        $velocityFindings = ComplianceFinding::ofType(FindingType::VelocityExceeded)->get();

        $this->assertCount(1, $velocityFindings);
        $this->assertEquals(FindingType::VelocityExceeded, $velocityFindings->first()->finding_type);
    }
}
