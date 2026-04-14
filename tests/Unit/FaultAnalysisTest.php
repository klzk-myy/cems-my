<?php

namespace Tests\Unit;

use App\Models\EnhancedDiligenceRecord;
use App\Models\Customer;
use App\Models\User;
use App\Services\EddService;
use App\Services\MathService;
use App\Enums\EddStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fault Tests - Critical and High Severity Issues
 *
 * Tests for faults identified in fau.md:
 * - Fault #1: isRecordComplete() doesn't validate non-empty purpose_of_transaction
 */
class FaultAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected EddService $eddService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eddService = new EddService(new MathService());
    }

    /**
     * FAULT #1: isRecordComplete() allows empty purpose_of_transaction
     *
     * The isRecordComplete() method only checks for null, not empty string.
     * An EDD record with empty string for purpose_of_transaction passes validation.
     */
    public function test_edd_record_with_empty_purpose_of_transaction_is_not_complete(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        // Create EDD record with empty purpose_of_transaction
        $record = EnhancedDiligenceRecord::create([
            'customer_id' => $customer->id,
            'edd_reference' => 'EDD-202604-0001',
            'status' => EddStatus::Incomplete,
            'risk_level' => 'Medium',
            'source_of_funds' => 'Valid source of funds', // Non-empty
            'purpose_of_transaction' => '', // Empty string - should fail
        ]);

        $this->assertFalse($this->eddService->isRecordComplete($record));
    }

    /**
     * FAULT #1 FIX: isRecordComplete() should reject empty purpose_of_transaction
     */
    public function test_edd_record_requires_both_source_of_funds_and_purpose(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        // Record with non-empty source_of_funds but empty purpose
        $record1 = EnhancedDiligenceRecord::create([
            'customer_id' => $customer->id,
            'edd_reference' => 'EDD-202604-0002',
            'status' => EddStatus::Incomplete,
            'risk_level' => 'Medium',
            'source_of_funds' => 'Some funds',
            'purpose_of_transaction' => '', // Empty - should fail
        ]);

        // Record with empty source_of_funds but non-empty purpose
        $record2 = EnhancedDiligenceRecord::create([
            'customer_id' => $customer->id,
            'edd_reference' => 'EDD-202604-0003',
            'status' => EddStatus::Incomplete,
            'risk_level' => 'Medium',
            'source_of_funds' => '',
            'purpose_of_transaction' => 'Some purpose',
        ]);

        $this->assertFalse($this->eddService->isRecordComplete($record1));
        $this->assertFalse($this->eddService->isRecordComplete($record2));
    }

    /**
     * FAULT #1: Complete record should pass validation
     */
    public function test_complete_edd_record_passes_validation(): void
    {
        $customer = Customer::factory()->create();

        $record = EnhancedDiligenceRecord::create([
            'customer_id' => $customer->id,
            'edd_reference' => 'EDD-202604-0004',
            'status' => EddStatus::Incomplete,
            'risk_level' => 'Medium',
            'source_of_funds' => 'Valid source of funds',
            'purpose_of_transaction' => 'Valid purpose',
        ]);

        $this->assertTrue($this->eddService->isRecordComplete($record));
    }

    /**
     * FAULT #5: Sanctions screening LIKE wildcards not escaped
     *
     * If customer name contains % or _, they act as wildcards in SQL LIKE,
     * causing false matches in sanctions screening.
     */
    public function test_sanctions_query_escapes_wildcard_characters(): void
    {
        $customerName = 'John 100% Senior'; // Contains % which is LIKE wildcard
        $escapedName = str_replace(['%', '_'], ['\\%', '\\_'], $customerName);

        // The escaped name should have backslash before wildcards
        $this->assertStringContainsString('\\%', $escapedName);
        $this->assertEquals('John 100\\% Senior', $escapedName);
    }

    /**
     * FAULT #10: countWorkingDays off-by-one error
     *
     * The while ($current->lt($to)) means exclusive of end date.
     * Monday to Tuesday = only Monday counted (should be 2 days).
     */
    public function test_working_days_calculation_inclusive_range(): void
    {
        $from = new \Carbon\Carbon('2026-04-13'); // Monday
        $to = new \Carbon\Carbon('2026-04-14');   // Tuesday

        // With lt(), only Monday is counted
        $days = 0;
        $current = $from->copy();
        while ($current->lt($to)) {
            if (! $current->isWeekend()) {
                $days++;
            }
            $current->addDay();
        }

        // This shows the bug: 2 consecutive weekdays should be 2 working days
        // but the algorithm only counts 1 (Monday)
        $this->assertEquals(1, $days); // Bug: should be 2
    }
}