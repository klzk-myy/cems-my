<?php

namespace Tests\Feature;

use App\Enums\ComplianceFlagType;
use App\Enums\FlagStatus;
use App\Enums\StrStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Models\StrReport;
use App\Models\SystemLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StrWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $tellerUser;

    protected User $managerUser;

    protected User $complianceOfficer;

    protected Customer $customer;

    protected Transaction $flaggedTransaction;

    protected FlaggedTransaction $flag;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => UserRole::Admin,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->complianceOfficer = User::create([
            'username' => 'compliance1',
            'email' => 'compliance@cems.my',
            'password_hash' => Hash::make('Compliance@1234'),
            'role' => UserRole::ComplianceOfficer,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Create customer
        $this->customer = Customer::create([
            'full_name' => 'Suspicious Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('999999999999'),
            'date_of_birth' => '1985-08-20',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('999 Suspicious Lane'),
            'contact_number_encrypted' => encrypt('0199999999'),
            'email' => 'suspicious@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'High',
        ]);

        // Create flagged transaction
        $this->flaggedTransaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'TILL-001',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '15000',
            'amount_local' => '70800.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Cash',
            'status' => TransactionStatus::Pending,
            'cdd_level' => 'Enhanced',
        ]);

        // Create compliance flag
        $this->flag = FlaggedTransaction::create([
            'transaction_id' => $this->flaggedTransaction->id,
            'flag_type' => ComplianceFlagType::Structuring,
            'flag_reason' => 'Multiple transactions just below reporting threshold',
            'status' => FlagStatus::Open,
            'assigned_to' => $this->complianceOfficer->id,
        ]);
    }

    /**
     * Test compliance officer can generate STR from flag
     */
    public function test_compliance_officer_can_generate_str_from_flag(): void
    {
        $response = $this->actingAs($this->complianceOfficer)
            ->post("/compliance/flags/{$this->flag->id}/generate-str");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // STR should be created
        $strReport = StrReport::where('alert_id', $this->flag->id)->first();
        $this->assertNotNull($strReport);
        $this->assertEquals(StrStatus::Draft, $strReport->status);
        $this->assertEquals($this->customer->id, $strReport->customer_id);

        // Flag should be updated
        $this->flag->refresh();
        $this->assertEquals(FlagStatus::UnderReview, $this->flag->status);
    }

    /**
     * Test STR is created with correct draft status
     */
    public function test_str_created_with_draft_status(): void
    {
        $this->actingAs($this->complianceOfficer)
            ->post("/compliance/flags/{$this->flag->id}/generate-str");

        $strReport = StrReport::where('alert_id', $this->flag->id)->first();

        $this->assertNotNull($strReport);
        $this->assertEquals(StrStatus::Draft, $strReport->status);
        $this->assertNotNull($strReport->str_no);
        $this->assertStringStartsWith('STR-', $strReport->str_no);
    }

    /**
     * Test STR generation creates system log
     */
    public function test_str_generation_creates_system_log(): void
    {
        $this->actingAs($this->complianceOfficer)
            ->post("/compliance/flags/{$this->flag->id}/generate-str");

        $strReport = StrReport::where('alert_id', $this->flag->id)->first();

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->complianceOfficer->id,
            'action' => 'str_generated',
            'entity_type' => 'StrReport',
            'entity_id' => $strReport->id,
        ]);
    }

    /**
     * Test compliance officer can submit STR for review
     */
    public function test_compliance_officer_can_submit_str_for_review(): void
    {
        // First generate the STR
        $this->actingAs($this->complianceOfficer)
            ->post("/compliance/flags/{$this->flag->id}/generate-str");

        $strReport = StrReport::where('alert_id', $this->flag->id)->first();

        // Submit for review
        $response = $this->actingAs($this->complianceOfficer)
            ->post("/str/{$strReport->id}/submit-review");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $strReport->refresh();
        $this->assertEquals(StrStatus::PendingReview, $strReport->status);
    }

    /**
     * Test manager can review and approve STR
     */
    public function test_manager_can_review_and_approve_str(): void
    {
        // Create STR in pending review status
        $strReport = StrReport::create([
            'str_no' => 'STR-20260400001',
            'branch_id' => 1,
            'customer_id' => $this->customer->id,
            'alert_id' => $this->flag->id,
            'transaction_ids' => [$this->flaggedTransaction->id],
            'reason' => 'Suspicious transaction pattern detected',
            'status' => StrStatus::PendingReview,
            'created_by' => $this->complianceOfficer->id,
        ]);

        // Manager submits for approval
        $response = $this->actingAs($this->managerUser)
            ->post("/str/{$strReport->id}/submit-approval");

        $response->assertRedirect();

        $strReport->refresh();
        $this->assertEquals(StrStatus::PendingApproval, $strReport->status);
        $this->assertEquals($this->managerUser->id, $strReport->reviewed_by);
    }

    /**
     * Test STR status transitions: draft → submitted_for_review → approved → submitted
     */
    public function test_str_status_transitions_correctly(): void
    {
        // Create STR in draft
        $strReport = StrReport::create([
            'str_no' => 'STR-20260400002',
            'branch_id' => 1,
            'customer_id' => $this->customer->id,
            'alert_id' => $this->flag->id,
            'transaction_ids' => [$this->flaggedTransaction->id],
            'reason' => 'Full suspicious transaction narrative',
            'status' => StrStatus::Draft,
            'created_by' => $this->complianceOfficer->id,
        ]);

        // Verify initial state
        $this->assertEquals(StrStatus::Draft, $strReport->status);
        $this->assertTrue($strReport->isDraft());

        // Submit for review
        $this->actingAs($this->complianceOfficer)
            ->post("/str/{$strReport->id}/submit-review");

        $strReport->refresh();
        $this->assertTrue($strReport->isPendingReview());

        // Manager reviews and submits for approval
        $this->actingAs($this->managerUser)
            ->post("/str/{$strReport->id}/submit-approval");

        $strReport->refresh();
        $this->assertEquals(StrStatus::PendingApproval, $strReport->status);
        $this->assertTrue($strReport->isPendingApproval());

        // Manager approves
        $this->actingAs($this->managerUser)
            ->post("/str/{$strReport->id}/approve");

        $strReport->refresh();
        $this->assertEquals(StrStatus::PendingApproval, $strReport->status);
        $this->assertEquals($this->managerUser->id, $strReport->approved_by);
    }

    /**
     * Test manager can submit STR to goAML (mock)
     */
    public function test_manager_can_submit_str_to_goaml(): void
    {
        // Create STR in pending approval status
        $strReport = StrReport::create([
            'str_no' => 'STR-20260400003',
            'branch_id' => 1,
            'customer_id' => $this->customer->id,
            'alert_id' => $this->flag->id,
            'transaction_ids' => [$this->flaggedTransaction->id],
            'reason' => 'Suspicious transaction narrative',
            'status' => StrStatus::PendingApproval,
            'created_by' => $this->complianceOfficer->id,
            'reviewed_by' => $this->managerUser->id,
            'approved_by' => $this->managerUser->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->post("/str/{$strReport->id}/submit");

        $response->assertRedirect();

        $strReport->refresh();
        $this->assertEquals(StrStatus::Submitted, $strReport->status);
        $this->assertNotNull($strReport->submitted_at);
    }

    /**
     * Test teller cannot access STR management
     */
    public function test_teller_cannot_access_str_management(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/str');

        $response->assertStatus(403);
    }

    /**
     * Test manager cannot generate STR (compliance only)
     */
    public function test_manager_cannot_generate_str(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->post("/compliance/flags/{$this->flag->id}/generate-str");

        $response->assertStatus(403);
    }

    /**
     * Test STR list page loads for compliance officer
     */
    public function test_str_list_page_loads_for_compliance(): void
    {
        $response = $this->actingAs($this->complianceOfficer)->get('/str');

        $response->assertStatus(200);
        $response->assertSee('STR Reports');
    }

    /**
     * Test compliance officer can view STR details
     */
    public function test_compliance_officer_can_view_str_details(): void
    {
        $strReport = StrReport::create([
            'str_no' => 'STR-20260400004',
            'branch_id' => 1,
            'customer_id' => $this->customer->id,
            'alert_id' => $this->flag->id,
            'transaction_ids' => [$this->flaggedTransaction->id],
            'reason' => 'Suspicious transaction details',
            'status' => StrStatus::Draft,
            'created_by' => $this->complianceOfficer->id,
        ]);

        $response = $this->actingAs($this->complianceOfficer)
            ->get("/str/{$strReport->id}");

        $response->assertStatus(200);
        $response->assertSee('STR-20260400004');
        $response->assertSee('Suspicious Customer');
    }

    /**
     * Test STR with filing deadline is set correctly
     */
    public function test_str_has_filing_deadline(): void
    {
        $this->actingAs($this->complianceOfficer)
            ->post("/compliance/flags/{$this->flag->id}/generate-str");

        $strReport = StrReport::where('alert_id', $this->flag->id)->first();

        $this->assertNotNull($strReport->filing_deadline);
        $this->assertNotNull($strReport->suspicion_date);
    }
}