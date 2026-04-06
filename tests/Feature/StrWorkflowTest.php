<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Models\StrReport;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\StrStatus;
use App\Enums\FlagStatus;
use App\Enums\ComplianceFlagType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StrWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $complianceUser;
    protected User $managerUser;
    protected User $adminUser;
    protected Customer $customer;
    protected Transaction $transaction;

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

        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => 'admin',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'full_name' => 'Test Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789012'),
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Malaysian',
            'email' => 'test@example.com',
            'pep_status' => false,
            'risk_score' => 30,
            'risk_rating' => 'Medium',
        ]);

        $this->transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->managerUser->id,
            'till_id' => 'TILL-001',
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000.00',
            'amount_local' => '4750.00',
            'rate' => '4.75',
            'status' => 'Completed',
        ]);
    }

    public function test_str_approve_sets_status_to_submitted(): void
    {
        // Create STR in pending_approval status
        $str = StrReport::create([
            'str_no' => 'STR-202604-00001',
            'branch_id' => 1,
            'customer_id' => $this->customer->id,
            'transaction_ids' => [$this->transaction->id],
            'reason' => 'Suspicious transaction pattern detected',
            'status' => StrStatus::PendingApproval,
            'created_by' => $this->complianceUser->id,
            'reviewed_by' => $this->complianceUser->id,
            'suspicion_date' => now(),
        ]);

        $response = $this->actingAs($this->managerUser)->post("/str/{$str->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $str->refresh();
        $this->assertEquals(StrStatus::Submitted, $str->status);
        $this->assertEquals($this->managerUser->id, $str->approved_by);
    }

    public function test_str_submit_for_review_requires_draft(): void
    {
        // Create STR in draft status
        $str = StrReport::create([
            'str_no' => 'STR-202604-00002',
            'branch_id' => 1,
            'customer_id' => $this->customer->id,
            'transaction_ids' => [$this->transaction->id],
            'reason' => 'Suspicious transaction pattern detected for review',
            'status' => StrStatus::Draft,
            'created_by' => $this->complianceUser->id,
            'suspicion_date' => now(),
        ]);

        $response = $this->actingAs($this->complianceUser)->post("/str/{$str->id}/submit-review");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $str->refresh();
        $this->assertEquals(StrStatus::PendingReview, $str->status);
    }

    public function test_str_submit_for_approval_requires_pending_review(): void
    {
        // Create STR in pending_review status
        $str = StrReport::create([
            'str_no' => 'STR-202604-00003',
            'branch_id' => 1,
            'customer_id' => $this->customer->id,
            'transaction_ids' => [$this->transaction->id],
            'reason' => 'Suspicious transaction pattern - pending approval',
            'status' => StrStatus::PendingReview,
            'created_by' => $this->complianceUser->id,
            'reviewed_by' => $this->complianceUser->id,
            'suspicion_date' => now(),
        ]);

        $response = $this->actingAs($this->complianceUser)->post("/str/{$str->id}/submit-approval");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $str->refresh();
        $this->assertEquals(StrStatus::PendingApproval, $str->status);
        $this->assertEquals($this->complianceUser->id, $str->reviewed_by);
    }

    public function test_str_generate_from_alert_creates_str(): void
    {
        // Create a flagged transaction (alert)
        $alert = FlaggedTransaction::create([
            'transaction_id' => $this->transaction->id,
            'customer_id' => $this->customer->id,
            'flag_type' => ComplianceFlagType::Structuring,
            'flag_reason' => 'Multiple small transactions just below reporting threshold',
            'status' => FlagStatus::Open,
        ]);

        // Log the alert details before request
        \Illuminate\Support\Facades\Log::info('Test creating alert', [
            'alert_id' => $alert->id,
            'alert_customer_id' => $alert->customer_id,
            'alert_transaction_id' => $alert->transaction_id,
        ]);

        $response = $this->actingAs($this->complianceUser)
            ->post("/compliance/flags/{$alert->id}/generate-str");

        \Illuminate\Support\Facades\Log::info('HTTP Response', [
            'status' => $response->status(),
            'is_redirect' => $response->isRedirect(),
            'location' => $response->headers->get('Location'),
        ]);

        // Get actual status for debugging
        $actualStatus = $response->status();

        if ($response->isRedirect()) {
            $hasSuccess = $response->getSession()->has('success');
            $hasError = $response->getSession()->has('error');
            $this->assertTrue($hasSuccess, 'Expected success message but got: ' . ($hasError ? 'error: ' . session('error') : 'no message'));
        } else {
            $this->fail("Expected redirect but got {$actualStatus}");
        }

        // Verify STR was created
        $this->assertDatabaseHas('str_reports', [
            'customer_id' => $this->customer->id,
            'alert_id' => $alert->id,
            'status' => StrStatus::Draft->value,
        ]);

        // Verify the STR was linked to the transaction
        $str = StrReport::where('alert_id', $alert->id)->first();
        $this->assertNotNull($str);
        $this->assertContains($this->transaction->id, $str->transaction_ids ?? []);
    }

    public function test_teller_cannot_access_str(): void
    {
        $teller = User::create([
            'username' => 'teller1',
            'email' => 'teller@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Try to access STR index
        $response = $this->actingAs($teller)->get('/str');
        $response->assertStatus(403);
    }

    public function test_compliance_cannot_approve_str(): void
    {
        // Create STR in pending_approval status
        $str = StrReport::create([
            'str_no' => 'STR-202604-00004',
            'branch_id' => 1,
            'customer_id' => $this->customer->id,
            'transaction_ids' => [$this->transaction->id],
            'reason' => 'Suspicious transaction - compliance cannot approve',
            'status' => StrStatus::PendingApproval,
            'created_by' => $this->complianceUser->id,
            'reviewed_by' => $this->complianceUser->id,
            'suspicion_date' => now(),
        ]);

        // Compliance officer tries to approve - route is protected by role:manager middleware
        // so compliance gets 403 Forbidden (correct segregation of duties)
        $response = $this->actingAs($this->complianceUser)->post("/str/{$str->id}/approve");

        $response->assertStatus(403);

        // STR status should not change
        $str->refresh();
        $this->assertEquals(StrStatus::PendingApproval, $str->status);
    }

    public function test_manager_can_approve_str(): void
    {
        // Create STR in pending_approval status
        $str = StrReport::create([
            'str_no' => 'STR-202604-00005',
            'branch_id' => 1,
            'customer_id' => $this->customer->id,
            'transaction_ids' => [$this->transaction->id],
            'reason' => 'Suspicious transaction - manager approval test',
            'status' => StrStatus::PendingApproval,
            'created_by' => $this->complianceUser->id,
            'reviewed_by' => $this->complianceUser->id,
            'suspicion_date' => now(),
        ]);

        $response = $this->actingAs($this->managerUser)->post("/str/{$str->id}/approve");

        $response->assertRedirect();

        $str->refresh();
        $this->assertEquals(StrStatus::Submitted, $str->status);
        $this->assertEquals($this->managerUser->id, $str->approved_by);
    }
}