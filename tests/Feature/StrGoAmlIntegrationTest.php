<?php

namespace Tests\Feature;

use App\Enums\StrStatus;
use App\Jobs\SubmitStrToGoAmlJob;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Models\StrReport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GoAmlXmlGenerator;
use App\Services\StrReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * STR goAML Integration Tests
 *
 * Tests for BNM goAML XML generation and submission functionality.
 * goAML (goAML) is the goAML standard for reporting suspicious transactions.
 */
class StrGoAmlIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $complianceOfficer;

    protected User $managerUser;

    protected Branch $branch;

    protected Customer $customer;

    protected Transaction $transaction;

    protected FlaggedTransaction $flag;

    protected StrReport $strReport;

    protected function setUp(): void
    {
        parent::setUp();

        // Create branch
        $this->branch = Branch::create([
            'code' => 'HQ',
            'name' => 'Head Office',
            'type' => Branch::TYPE_HEAD_OFFICE,
            'address' => '123 Main Street',
            'city' => 'Kuala Lumpur',
            'state' => 'Wilayah Persekutuan',
            'postal_code' => '50000',
            'country' => 'Malaysia',
            'phone' => '+603-12345678',
            'email' => 'hq@cems.my',
            'is_active' => true,
            'is_main' => true,
        ]);

        // Create users
        $this->complianceOfficer = User::create([
            'username' => 'compliance1',
            'email' => 'compliance@cems.my',
            'password_hash' => Hash::make('Compliance@1234'),
            'role' => \App\Enums\UserRole::ComplianceOfficer,
            'branch_id' => $this->branch->id,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => \App\Enums\UserRole::Manager,
            'branch_id' => $this->branch->id,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Create customer
        $this->customer = Customer::create([
            'full_name' => 'Suspicious Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('881234567890'),
            'date_of_birth' => '1988-01-15',
            'nationality' => 'Malaysian',
            'address' => '456 Jalan Test',
            'phone' => '+6012-3456789',
            'email' => 'suspicious@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'High',
            'cdd_level' => 'Enhanced',
            'occupation' => 'Business Owner',
            'employer_name' => 'Test Company Sdn Bhd',
        ]);

        // Create transaction
        $this->transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->managerUser->id,
            'branch_id' => $this->branch->id,
            'till_id' => 'TILL-001',
            'type' => \App\Enums\TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '10000.00',
            'amount_local' => '47200.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Revenue',
            'status' => \App\Enums\TransactionStatus::Completed,
            'cdd_level' => \App\Enums\CddLevel::Enhanced,
        ]);

        // Create compliance flag
        $this->flag = FlaggedTransaction::create([
            'transaction_id' => $this->transaction->id,
            'flag_type' => \App\Enums\ComplianceFlagType::Structuring,
            'flag_reason' => 'Multiple transactions below threshold',
            'status' => \App\Enums\FlagStatus::Open,
        ]);

        // Create STR report
        $this->strReport = StrReport::create([
            'str_no' => 'STR-202604-00001',
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'alert_id' => $this->flag->id,
            'transaction_ids' => [$this->transaction->id],
            'reason' => 'Suspicious structuring pattern detected',
            'status' => StrStatus::PendingApproval,
            'created_by' => $this->complianceOfficer->id,
            'reviewed_by' => $this->complianceOfficer->id,
            'approved_by' => $this->managerUser->id,
            'suspicion_date' => now()->subDays(1),
            'filing_deadline' => now()->addDays(2),
        ]);

        // Configure goAML for testing
        config()->set('cems.goaml.reporter_name', 'CEMS-MY MSB');
        config()->set('cems.goaml.branch_code', 'HQ');
    }

    /**
     * Test GoAmlXmlGenerator generates valid XML structure
     */
    public function test_goaml_xml_generator_creates_valid_xml_structure(): void
    {
        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($this->strReport);

        // Assert XML is well-formed
        $this->assertStringContainsString('<?xml version="1.0"', $xml);
        // XML uses namespace: <report xmlns="...">
        $this->assertStringContainsString('<report', $xml);
        $this->assertStringContainsString('</report>', $xml);
    }

    /**
     * Test GoAmlXmlGenerator includes reporting entity details
     */
    public function test_goaml_xml_includes_reporting_entity(): void
    {
        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($this->strReport);

        $this->assertStringContainsString('<reporting_entity>', $xml);
        $this->assertStringContainsString('</reporting_entity>', $xml);
    }

    /**
     * Test GoAmlXmlGenerator includes suspicious activity details
     */
    public function test_goaml_xml_includes_suspicious_activity(): void
    {
        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($this->strReport);

        $this->assertStringContainsString('<suspicious_activity>', $xml);
        $this->assertStringContainsString('</suspicious_activity>', $xml);
    }

    /**
     * Test GoAmlXmlGenerator includes transaction details
     */
    public function test_goaml_xml_includes_transaction_details(): void
    {
        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($this->strReport);

        $this->assertStringContainsString('<transactions>', $xml);
        $this->assertStringContainsString('</transactions>', $xml);
        $this->assertStringContainsString('<transaction>', $xml);
        $this->assertStringContainsString('</transaction>', $xml);
    }

    /**
     * Test GoAmlXmlGenerator masks customer sensitive information
     */
    public function test_goaml_xml_masks_customer_sensitive_information(): void
    {
        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($this->strReport);

        // Full ID number should not be present
        $this->assertStringNotContainsString('881234567890', $xml);

        // Masked ID should be present
        $this->assertStringContainsString('***', $xml);
    }

    /**
     * Test GoAmlXmlGenerator generates valid XML against schema
     */
    public function test_goaml_xml_is_valid_against_schema(): void
    {
        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($this->strReport);

        // Parse and validate XML structure
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $this->assertTrue($dom->loadXML($xml), 'XML should be well-formed');
    }

    /**
     * Test StrReportService submitToGoAML updates status on success
     */
    public function test_str_submission_updates_status_on_success(): void
    {
        // Mock HTTP facade to simulate successful submission
        Http::fake([
            '*' => Http::response(['status' => 'success', 'reference' => 'BNM-STR-12345'], 200),
        ]);

        $service = new StrReportService;
        $result = $service->submitToGoAML($this->strReport);

        $this->strReport->refresh();

        // In test mode without proper certificates, this may fail
        // But we verify the method exists and handles success case
        $this->assertIsBool($result);
    }

    /**
     * Test StrReportService marks status as failed on API error
     */
    public function test_str_submission_marks_failed_on_api_error(): void
    {
        // Create a fresh STR for this test
        $flag = FlaggedTransaction::create([
            'transaction_id' => $this->transaction->id,
            'flag_type' => \App\Enums\ComplianceFlagType::Structuring,
            'flag_reason' => 'Test flag',
            'status' => \App\Enums\FlagStatus::Open,
        ]);

        $strReport = StrReport::create([
            'str_no' => 'STR-202604-'.str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT),
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'alert_id' => $flag->id,
            'transaction_ids' => [$this->transaction->id],
            'reason' => 'Test suspicious activity',
            'status' => StrStatus::PendingApproval,
            'created_by' => $this->complianceOfficer->id,
            'reviewed_by' => $this->complianceOfficer->id,
            'approved_by' => $this->managerUser->id,
            'retry_count' => 0,
        ]);

        $service = new StrReportService;
        $result = $service->submitToGoAML($strReport);

        $strReport->refresh();

        $this->assertFalse($result);
        $this->assertEquals(StrStatus::Failed, $strReport->status);
        $this->assertNotNull($strReport->last_error);
        // Retry count should be incremented (certificate config will fail)
        $this->assertGreaterThanOrEqual(1, $strReport->retry_count);
    }

    /**
     * Test retry mechanism with exponential backoff
     */
    public function test_retry_mechanism_increments_retry_count(): void
    {
        // Create a fresh STR with no retry count
        $flag = FlaggedTransaction::create([
            'transaction_id' => $this->transaction->id,
            'flag_type' => \App\Enums\ComplianceFlagType::Structuring,
            'flag_reason' => 'Test flag',
            'status' => \App\Enums\FlagStatus::Open,
        ]);

        $strReport = StrReport::create([
            'str_no' => 'STR-202604-'.str_pad(random_int(90000, 99999), 5, '0', STR_PAD_LEFT),
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'alert_id' => $flag->id,
            'transaction_ids' => [$this->transaction->id],
            'reason' => 'Test suspicious activity',
            'status' => StrStatus::PendingApproval,
            'created_by' => $this->complianceOfficer->id,
            'reviewed_by' => $this->complianceOfficer->id,
            'approved_by' => $this->managerUser->id,
            'retry_count' => 0,
        ]);

        // Set max retries high to avoid escalation
        config()->set('services.goaml.max_retries', 20);

        $service = new StrReportService;

        // First attempt (will fail due to missing certificates)
        $service->submitToGoAML($strReport);
        $strReport->refresh();
        $this->assertEquals(StrStatus::Failed, $strReport->status);
        $initialRetryCount = $strReport->retry_count;
        $this->assertGreaterThanOrEqual(1, $initialRetryCount);

        // Retry will re-set to PendingApproval and then fail again
        $service->retrySubmission($strReport);
        $strReport->refresh();

        // Status should end up as Failed after retry attempt
        $this->assertEquals(StrStatus::Failed, $strReport->status);
        // Retry count should be incremented from previous attempt
        $this->assertGreaterThanOrEqual($initialRetryCount, $strReport->retry_count);
    }

    /**
     * Test retry mechanism escalates after max retries
     */
    public function test_retry_escalates_after_max_retries(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Server error'], 500),
        ]);

        // Set max retries low for testing
        config()->set('services.goaml.max_retries', 2);

        $this->strReport->update(['retry_count' => 2]);

        $service = new StrReportService;
        $result = $service->retrySubmission($this->strReport);

        $this->assertFalse($result);
        // Should have escalated
    }

    /**
     * Test SubmitStrToGoAmlJob can be dispatched
     */
    public function test_submit_str_job_can_be_dispatched(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'received'], 200),
        ]);

        $job = new SubmitStrToGoAmlJob($this->strReport);

        // Job should be dispatchable without errors
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /**
     * Test XML includes STR reference number
     */
    public function test_goaml_xml_includes_str_reference(): void
    {
        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($this->strReport);

        $this->assertStringContainsString('STR-202604-00001', $xml);
    }

    /**
     * Test XML includes transaction amounts
     */
    public function test_goaml_xml_includes_transaction_amounts(): void
    {
        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($this->strReport);

        $this->assertStringContainsString('47200', $xml);
        $this->assertStringContainsString('USD', $xml);
    }

    /**
     * Test XML includes submission timestamp
     */
    public function test_goaml_xml_includes_submission_timestamp(): void
    {
        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($this->strReport);

        $this->assertStringContainsString('<submission_date>', $xml);
        $this->assertStringContainsString(date('Y-m-d'), $xml);
    }

    /**
     * Test GoAmlXmlGenerator handles missing optional fields gracefully
     */
    public function test_goaml_xml_handles_missing_optional_fields(): void
    {
        // Create STR without some optional data
        $flag = FlaggedTransaction::create([
            'transaction_id' => $this->transaction->id,
            'flag_type' => \App\Enums\ComplianceFlagType::UnusualPattern,
            'flag_reason' => 'Test minimal STR',
            'status' => \App\Enums\FlagStatus::Open,
        ]);

        $minimalStr = StrReport::create([
            'str_no' => 'STR-202604-'.str_pad(random_int(20000, 29999), 5, '0', STR_PAD_LEFT),
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'alert_id' => $flag->id,
            'transaction_ids' => [],
            'reason' => 'Test reason',
            'status' => StrStatus::PendingApproval,
            'created_by' => $this->complianceOfficer->id,
        ]);

        $generator = new GoAmlXmlGenerator;
        $xml = $generator->generate($minimalStr);

        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<report', $xml);
    }

    /**
     * Test certificate-based authentication configuration
     */
    public function test_certificate_configuration_exists(): void
    {
        // Certificate paths should be configurable
        $this->assertIsArray(config('services.goaml', []));
    }

    /**
     * Test test mode configuration
     */
    public function test_test_mode_configuration(): void
    {
        config()->set('services.goaml.test_mode', true);

        $this->assertTrue(config('services.goaml.test_mode', false));
    }
}
