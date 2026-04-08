<?php

namespace Tests\Feature\Api\Compliance;

use App\Enums\EddStatus;
use App\Enums\UserRole;
use App\Models\Compliance\EddQuestionnaireTemplate;
use App\Models\Customer;
use App\Models\EnhancedDiligenceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EddControllerTest extends TestCase
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
     * Test can list EDD records.
     */
    public function test_can_list_edd_records(): void
    {
        $customer = Customer::factory()->create();

        EnhancedDiligenceRecord::factory()->count(3)->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/edd');

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
     * Test can filter EDD by status.
     */
    public function test_can_filter_edd_by_status(): void
    {
        $customer = Customer::factory()->create();

        // Create an Approved record
        EnhancedDiligenceRecord::factory()->create([
            'customer_id' => $customer->id,
            'status' => EddStatus::Approved,
        ]);

        // Create a PendingQuestionnaire record
        EnhancedDiligenceRecord::factory()->create([
            'customer_id' => $customer->id,
            'status' => EddStatus::PendingQuestionnaire,
        ]);

        // Filter by Approved status - should get 1
        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/edd?status=Approved');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
        $this->assertEquals('Approved', $response->json('data.0.status'));
    }

    /**
     * Test can get EDD templates.
     */
    public function test_can_get_edd_templates(): void
    {
        // Create active templates
        EddQuestionnaireTemplate::factory()->count(2)->create([
            'is_active' => true,
        ]);

        // Create inactive template
        EddQuestionnaireTemplate::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->getJson('/api/compliance/edd/templates');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        // Should only return active templates (2)
        $this->assertEquals(2, count($response->json('data')));
    }

    /**
     * Test can submit questionnaire.
     */
    public function test_can_submit_questionnaire(): void
    {
        $customer = Customer::factory()->create();

        $record = EnhancedDiligenceRecord::factory()->create([
            'customer_id' => $customer->id,
            'status' => EddStatus::PendingQuestionnaire,
        ]);

        $response = $this->actingAs($this->complianceUser, 'sanctum')
            ->postJson("/api/compliance/edd/{$record->id}/questionnaire", [
                'responses' => [
                    'q1' => 'Salary from employment',
                    'q2' => 'Yes',
                    'q3' => 'Investment purposes',
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'status' => 'Questionnaire_Submitted',
            ],
        ]);

        $record->refresh();
        $this->assertEquals(EddStatus::QuestionnaireSubmitted, $record->status);
    }
}
