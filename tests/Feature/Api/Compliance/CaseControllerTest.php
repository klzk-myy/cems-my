<?php

namespace Tests\Feature\Api\Compliance;

use App\Enums\CaseNoteType;
use App\Enums\CaseResolution;
use App\Enums\ComplianceCaseStatus;
use App\Enums\ComplianceCaseType;
use App\Enums\FindingStatus;
use App\Enums\UserRole;
use App\Models\Compliance\ComplianceCase;
use App\Models\Compliance\ComplianceCaseNote;
use App\Models\Compliance\ComplianceFinding;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CaseControllerTest extends TestCase
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
     * Test can list compliance cases.
     */
    public function test_can_list_cases(): void
    {
        $customer = Customer::factory()->create();

        ComplianceCase::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/cases');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
            'per_page',
            'total',
        ]);
        $this->assertEquals(3, $response->json('total'));
    }

    /**
     * Test can filter cases by status.
     */
    public function test_can_filter_cases_by_status(): void
    {
        $customer = Customer::factory()->create();

        // Create an Open case
        ComplianceCase::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
            'status' => ComplianceCaseStatus::Open,
        ]);

        // Create a Closed case
        ComplianceCase::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
            'status' => ComplianceCaseStatus::Closed,
        ]);

        // Filter by Open status - should get 1
        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/cases?status=Open');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
        $this->assertEquals('Open', $response->json('data.0.status'));

        // Filter by Closed status - should get 1 (the Closed case)
        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/cases?status=Closed');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
        $this->assertEquals('Closed', $response->json('data.0.status'));
    }

    /**
     * Test can create a case from a finding.
     */
    public function test_can_create_case_from_finding(): void
    {
        $customer = Customer::factory()->create();

        $finding = ComplianceFinding::factory()->create([
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'status' => FindingStatus::New,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->postJson('/api/compliance/cases', [
                'finding_id' => $finding->id,
                'case_type' => 'Investigation',
                'assigned_to' => $this->complianceUser->id,
                'summary' => 'Test case from finding',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => [
            'id',
            'case_number',
            'case_type',
            'status',
            'severity',
            'case_summary',
        ]]);

        $this->assertDatabaseHas('compliance_cases', [
            'primary_finding_id' => $finding->id,
            'case_type' => ComplianceCaseType::Investigation->value,
            'assigned_to' => $this->complianceUser->id,
        ]);

        $finding->refresh();
        $this->assertEquals(FindingStatus::CaseCreated, $finding->status);
    }

    /**
     * Test can add a note to a case.
     */
    public function test_can_add_note(): void
    {
        $customer = Customer::factory()->create();

        $case = ComplianceCase::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->postJson("/api/compliance/cases/{$case->id}/notes", [
                'note_type' => 'Investigation',
                'content' => 'This is a test note',
                'is_internal' => true,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => [
            'id',
            'case_id',
            'author_id',
            'note_type',
            'content',
            'is_internal',
        ]]);

        $this->assertDatabaseHas('compliance_case_notes', [
            'case_id' => $case->id,
            'content' => 'This is a test note',
        ]);
    }

    /**
     * Test can close a case.
     */
    public function test_can_close_case(): void
    {
        $customer = Customer::factory()->create();

        $case = ComplianceCase::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
            'status' => ComplianceCaseStatus::UnderReview,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->postJson("/api/compliance/cases/{$case->id}/close", [
                'resolution' => 'NoConcern',
                'notes' => 'Case reviewed and closed with no concerns',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'status' => 'Closed',
            ],
        ]);

        $case->refresh();
        $this->assertEquals(ComplianceCaseStatus::Closed, $case->status);
        $this->assertEquals(CaseResolution::NoConcern->value, $case->resolution);
    }

    /**
     * Test can get case timeline.
     */
    public function test_can_get_case_timeline(): void
    {
        $customer = Customer::factory()->create();

        $case = ComplianceCase::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $this->complianceUser->id,
        ]);

        // Add a note
        ComplianceCaseNote::factory()->create([
            'case_id' => $case->id,
            'author_id' => $this->complianceUser->id,
            'note_type' => CaseNoteType::Investigation,
            'content' => 'Timeline test note',
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson("/api/compliance/cases/{$case->id}/timeline");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [
            '*' => [
                'type',
                'timestamp',
            ],
        ]]);

        // Should have created event and note event
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }
}
