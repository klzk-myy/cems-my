<?php

namespace Tests\Unit;

use App\Enums\ComplianceFlagType;
use App\Enums\StrStatus;
use App\Models\Alert;
use App\Models\Compliance\ComplianceCase;
use App\Models\Customer;
use App\Models\FlaggedTransaction;
use App\Models\StrDraft;
use App\Models\StrReport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StrAutomationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StrAutomationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StrAutomationService::class);
    }

    public function test_suggest_narrative_with_velocity(): void
    {
        $narrative = $this->service->suggestNarrative(
            [ComplianceFlagType::Velocity],
            ['total_amount' => 150000]
        );

        $this->assertStringContainsString('RM 150,000', $narrative);
        $this->assertStringContainsString('velocity', strtolower($narrative));
    }

    public function test_suggest_narrative_with_structuring(): void
    {
        $narrative = $this->service->suggestNarrative(
            [ComplianceFlagType::Structuring],
            ['sub_threshold_count' => 5]
        );

        $this->assertStringContainsString('5 transactions', $narrative);
        $this->assertStringContainsString('structuring', strtolower($narrative));
    }

    public function test_suggest_narrative_with_sanctions(): void
    {
        $narrative = $this->service->suggestNarrative(
            [ComplianceFlagType::SanctionMatch],
            []
        );

        $this->assertStringContainsString('sanctions', strtolower($narrative));
    }

    public function test_suggest_narrative_with_high_risk_country(): void
    {
        $narrative = $this->service->suggestNarrative(
            [ComplianceFlagType::HighRiskCountry],
            []
        );

        $this->assertStringContainsString('high-risk country', strtolower($narrative));
    }

    public function test_suggest_narrative_with_large_amount(): void
    {
        $narrative = $this->service->suggestNarrative(
            [ComplianceFlagType::LargeAmount],
            ['max_amount' => 120000]
        );

        $this->assertStringContainsString('RM 120,000', $narrative);
    }

    public function test_suggest_narrative_empty_alerts_returns_default(): void
    {
        $narrative = $this->service->suggestNarrative([], []);

        $this->assertStringContainsString('unusual pattern', strtolower($narrative));
    }

    public function test_suggest_narrative_combines_multiple_flags(): void
    {
        $narrative = $this->service->suggestNarrative(
            [
                ComplianceFlagType::Velocity,
                ComplianceFlagType::LargeAmount,
            ],
            [
                'total_amount' => 200000,
                'max_amount' => 150000,
            ]
        );

        $this->assertStringContainsString('RM 200,000', $narrative);
        $this->assertStringContainsString('RM 150,000', $narrative);
    }

    public function test_convert_to_str_report_creates_report(): void
    {
        $customer = Customer::factory()->create();
        $strDraft = StrDraft::create([
            'customer_id' => $customer->id,
            'status' => StrStatus::Draft,
            'narrative' => 'Test narrative',
            'suspected_activity' => 'Test activity',
            'transaction_ids' => [],
            'confidence_score' => 85,
            'filing_deadline' => now()->addHours(24), // Within 48h for canConvert
        ]);

        $strReport = $this->service->convertToStrReport($strDraft);

        $this->assertInstanceOf(StrReport::class, $strReport);
        $this->assertEquals($customer->id, $strReport->customer_id);
        $this->assertEquals(StrStatus::Draft, $strReport->status);
        $this->assertEquals('Test narrative', $strReport->reason);

        $strDraft->refresh();
        $this->assertEquals(StrStatus::Submitted, $strDraft->status);
        $this->assertEquals($strReport->id, $strDraft->converted_to_str_id);
    }

    public function test_convert_to_str_report_fails_when_not_convertible(): void
    {
        // canConvert requires: confidence_score >= 80 AND filing_deadline within 48h
        $customer = Customer::factory()->create();
        $strDraft = StrDraft::create([
            'customer_id' => $customer->id,
            'status' => StrStatus::Draft,
            'confidence_score' => 50, // Too low
            'filing_deadline' => now()->addDays(7), // Too far
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('STR draft does not meet conversion criteria');

        $this->service->convertToStrReport($strDraft);
    }

    public function test_get_overdue_drafts_returns_only_overdue(): void
    {
        StrDraft::create([
            'status' => StrStatus::Draft,
            'filing_deadline' => now()->subDays(1),
            'customer_id' => Customer::factory()->create()->id,
            'confidence_score' => 80,
        ]);
        StrDraft::create([
            'status' => StrStatus::Draft,
            'filing_deadline' => now()->addDays(5),
            'customer_id' => Customer::factory()->create()->id,
            'confidence_score' => 80,
        ]);

        $overdue = $this->service->getOverdueDrafts();

        $this->assertCount(1, $overdue);
    }

    public function test_get_filing_deadline_summary(): void
    {
        StrDraft::create([
            'status' => StrStatus::Draft,
            'filing_deadline' => now()->addDays(1),
            'customer_id' => Customer::factory()->create()->id,
            'confidence_score' => 80,
        ]);
        StrDraft::create([
            'status' => StrStatus::Draft,
            'filing_deadline' => now()->addDays(2),
            'customer_id' => Customer::factory()->create()->id,
            'confidence_score' => 80,
        ]);
        StrDraft::create([
            'status' => StrStatus::Draft,
            'filing_deadline' => now()->subDays(1),
            'customer_id' => Customer::factory()->create()->id,
            'confidence_score' => 80,
        ]);
        StrDraft::create([
            'status' => StrStatus::Submitted,
            'filing_deadline' => now()->addDays(1),
            'customer_id' => Customer::factory()->create()->id,
            'confidence_score' => 80,
        ]);

        $summary = $this->service->getFilingDeadlineSummary();

        $this->assertEquals(3, $summary['total_pending']);
        $this->assertEquals(1, $summary['overdue']);
    }

    public function test_extract_transaction_patterns(): void
    {
        $customer = Customer::factory()->create();
        $transaction1 = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => 25000,
            'currency_code' => 'USD',
        ]);
        $transaction2 = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'amount_local' => 35000,
            'currency_code' => 'EUR',
        ]);

        // Test the service's protected method via reflection
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractTransactionPatterns');
        $method->setAccessible(true);

        $patterns = $method->invoke($this->service, collect([$transaction1, $transaction2]));

        $this->assertEquals(60000, $patterns['total_amount']);
        $this->assertEquals(35000, $patterns['max_amount']);
        $this->assertEquals(2, $patterns['sub_threshold_count']);
        $this->assertContains('USD', $patterns['currency_codes']);
        $this->assertContains('EUR', $patterns['currency_codes']);
    }

    public function test_identify_suspected_activity(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('identifySuspectedActivity');
        $method->setAccessible(true);

        $activity = $method->invoke($this->service, collect([
            (object) ['type' => ComplianceFlagType::Velocity],
            (object) ['type' => ComplianceFlagType::SanctionMatch],
        ]));

        $this->assertStringContainsString('Velocity', $activity);
        $this->assertStringContainsString('Sanctions', $activity);
    }
}
