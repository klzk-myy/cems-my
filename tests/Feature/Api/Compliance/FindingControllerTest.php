<?php

namespace Tests\Feature\Api\Compliance;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\FindingType;
use App\Enums\UserRole;
use App\Models\Compliance\ComplianceFinding;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FindingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $complianceUser;
    protected User $tellerUser;

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

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Test can list compliance findings.
     */
    public function test_can_list_findings(): void
    {
        $customer = Customer::factory()->create();

        ComplianceFinding::factory()->count(3)->create([
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/findings');

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
     * Test can filter findings by status.
     */
    public function test_can_filter_findings_by_status(): void
    {
        $customer = Customer::factory()->create();

        ComplianceFinding::factory()->create([
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'status' => FindingStatus::New,
        ]);

        ComplianceFinding::factory()->create([
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'status' => FindingStatus::Dismissed,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/findings?status=Dismissed');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
        $this->assertEquals('Dismissed', $response->json('data.0.status'));
    }

    /**
     * Test can get finding statistics.
     */
    public function test_can_get_finding_stats(): void
    {
        $customer = Customer::factory()->create();

        ComplianceFinding::factory()->create([
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'severity' => FindingSeverity::High,
            'status' => FindingStatus::New,
            'finding_type' => FindingType::VelocityExceeded,
        ]);

        ComplianceFinding::factory()->create([
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'severity' => FindingSeverity::Critical,
            'status' => FindingStatus::Dismissed,
            'finding_type' => FindingType::SanctionMatch,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/findings/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total',
            'new',
            'by_severity',
            'by_status',
            'by_type',
        ]);
        $this->assertEquals(2, $response->json('total'));
        $this->assertEquals(1, $response->json('new'));
    }

    /**
     * Test can dismiss a finding.
     */
    public function test_can_dismiss_finding(): void
    {
        $customer = Customer::factory()->create();

        $finding = ComplianceFinding::factory()->create([
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'status' => FindingStatus::New,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->postJson("/api/compliance/findings/{$finding->id}/dismiss", [
                'reason' => 'False positive - customer verified',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Finding dismissed']);

        $finding->refresh();
        $this->assertEquals(FindingStatus::Dismissed, $finding->status);
    }

    /**
     * Test dismiss requires reason.
     */
    public function test_dismiss_requires_reason(): void
    {
        $customer = Customer::factory()->create();

        $finding = ComplianceFinding::factory()->create([
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'status' => FindingStatus::New,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->postJson("/api/compliance/findings/{$finding->id}/dismiss");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }
}
