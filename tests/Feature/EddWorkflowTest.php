<?php

namespace Tests\Feature;

use App\Enums\EddStatus;
use App\Models\Customer;
use App\Models\EnhancedDiligenceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EddWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $complianceUser;

    protected User $managerUser;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->complianceUser = User::create([
            'username' => 'compliance1',
            'email' => 'compliance@cems.my',
            'password_hash' => Hash::make('Compliance@1234'),
            'role' => 'compliance_officer',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => 'manager',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'full_name' => 'Test Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => 'encrypted_id_123',
            'nationality' => 'Malaysian',
            'date_of_birth' => '1990-01-01',
            'email' => 'test@example.com',
            'pep_status' => true,
            'risk_score' => 85,
            'risk_rating' => 'High',
        ]);
    }

    public function test_edd_index_accessible_by_compliance(): void
    {
        $response = $this->actingAs($this->complianceUser)->get('/compliance/edd');
        $response->assertStatus(200);
    }

    public function test_edd_create_page_loads(): void
    {
        $response = $this->actingAs($this->complianceUser)->get('/compliance/edd/create');
        $response->assertStatus(200);
    }

    public function test_can_create_edd_record(): void
    {
        $response = $this->actingAs($this->complianceUser)->post('/compliance/edd', [
            'customer_id' => $this->customer->id,
            'risk_level' => 'High',
            'source_of_funds' => 'Salary',
            'purpose_of_transaction' => 'Personal Transaction',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('enhanced_diligence_records', [
            'customer_id' => $this->customer->id,
            'status' => 'Pending_Review',
        ]);
    }

    public function test_edd_record_requires_source_of_funds(): void
    {
        $response = $this->actingAs($this->complianceUser)->post('/compliance/edd', [
            'customer_id' => $this->customer->id,
            'risk_level' => 'High',
            'purpose_of_transaction' => 'Personal Transaction',
        ]);

        $response->assertSessionHasErrors('source_of_funds');
    }

    public function test_can_view_edd_record(): void
    {
        $record = EnhancedDiligenceRecord::create([
            'customer_id' => $this->customer->id,
            'edd_reference' => 'EDD-202604-0001',
            'status' => 'Incomplete',
            'risk_level' => 'High',
            'source_of_funds' => 'Salary',
            'purpose_of_transaction' => 'Personal Transaction',
        ]);

        $response = $this->actingAs($this->complianceUser)->get("/compliance/edd/{$record->id}");
        $response->assertStatus(200);
        $response->assertSee('EDD-202604-0001');
    }

    public function test_can_update_and_complete_edd_record(): void
    {
        $record = EnhancedDiligenceRecord::create([
            'customer_id' => $this->customer->id,
            'edd_reference' => 'EDD-202604-0002',
            'status' => EddStatus::Incomplete,
            'risk_level' => 'High',
            'source_of_funds' => 'Salary',
            'purpose_of_transaction' => 'Personal Transaction',
        ]);

        $response = $this->actingAs($this->complianceUser)->put("/compliance/edd/{$record->id}", [
            'source_of_funds' => 'Business Income',
            'purpose_of_transaction' => 'Business Payment',
            'business_justification' => 'Payment for import goods',
        ]);

        $response->assertRedirect();

        $record->refresh();
        $this->assertEquals(EddStatus::PendingReview, $record->status);
    }

    public function test_can_approve_edd_record(): void
    {
        $record = EnhancedDiligenceRecord::create([
            'customer_id' => $this->customer->id,
            'edd_reference' => 'EDD-202604-0003',
            'status' => EddStatus::PendingReview,
            'risk_level' => 'High',
            'source_of_funds' => 'Salary',
            'purpose_of_transaction' => 'Personal Transaction',
        ]);

        $response = $this->actingAs($this->managerUser)->post("/compliance/edd/{$record->id}/approve", [
            'notes' => 'Verified and approved',
        ]);

        $response->assertRedirect();

        $record->refresh();
        $this->assertEquals(EddStatus::Approved, $record->status);
        $this->assertNotNull($record->reviewed_at);
    }

    public function test_can_reject_edd_record(): void
    {
        $record = EnhancedDiligenceRecord::create([
            'customer_id' => $this->customer->id,
            'edd_reference' => 'EDD-202604-0004',
            'status' => EddStatus::PendingReview,
            'risk_level' => 'High',
            'source_of_funds' => 'Salary',
            'purpose_of_transaction' => 'Personal Transaction',
        ]);

        $response = $this->actingAs($this->managerUser)->post("/compliance/edd/{$record->id}/reject", [
            'reason' => 'Insufficient documentation provided',
        ]);

        $response->assertRedirect();

        $record->refresh();
        $this->assertEquals(EddStatus::Rejected, $record->status);
        $this->assertEquals('Insufficient documentation provided', $record->review_notes);
    }

    public function test_edd_reference_is_auto_generated(): void
    {
        $response = $this->actingAs($this->complianceUser)->post('/compliance/edd', [
            'customer_id' => $this->customer->id,
            'risk_level' => 'Medium',
            'source_of_funds' => 'Savings',
            'purpose_of_transaction' => 'Travel',
        ]);

        $record = EnhancedDiligenceRecord::orderBy('id', 'desc')->first();
        $this->assertStringStartsWith('EDD-', $record->edd_reference);
    }
}
