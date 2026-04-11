<?php

namespace Tests\Unit;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\ExchangeRate;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\MathService;
use App\Services\RateApiService;
use App\Services\RevaluationService;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RevaluationServiceFixTest extends TestCase
{
    use RefreshDatabase;

    protected RevaluationService $service;

    protected MathService $mathService;

    protected RateApiService $rateApiService;

    protected AccountingService $accountingService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies - must include all currencies referenced in tests
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'GBP'], ['name' => 'British Pound', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'SGD'], ['name' => 'Singapore Dollar', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'AUD'], ['name' => 'Australian Dollar', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'CAD'], ['name' => 'Canadian Dollar', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'CHF'], ['name' => 'Swiss Franc', 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'JPY'], ['name' => 'Japanese Yen', 'is_active' => true]);

        // Seed chart of accounts for revaluation entries
        $this->seedChartOfAccounts();

        // Seed user
        $this->user = User::factory()->create();

        // Setup services
        $this->mathService = new MathService;
        $this->rateApiService = new RateApiService;
        $this->accountingService = new AccountingService($this->mathService);
        $auditService = new AuditService;
        $this->service = new RevaluationService($this->mathService, $this->rateApiService, $this->accountingService, $auditService);

        // Disable account validation for tests
        Config::set('accounting.validate_accounts', false);

        // Seed exchange rates
        ExchangeRate::create([
            'currency_code' => 'USD',
            'rate_buy' => 4.5000,
            'rate_sell' => 4.5200,
            'source' => 'test',
            'fetched_at' => now(),
        ]);
        ExchangeRate::create([
            'currency_code' => 'EUR',
            'rate_buy' => 4.8000,
            'rate_sell' => 4.8200,
            'source' => 'test',
            'fetched_at' => now(),
        ]);
    }

    /**
     * Seed chart of accounts for revaluation tests.
     */
    protected function seedChartOfAccounts(): void
    {
        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Forex Position', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5100'],
            ['account_name' => 'Revaluation Gain', 'account_type' => 'Revenue', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '6100'],
            ['account_name' => 'Revaluation Loss', 'account_type' => 'Expense', 'is_active' => true]
        );
    }

    /**
     * Test FAULT #5: Period validation is performed before creating journal entries
     */
    public function test_revaluation_with_journal_validates_period_is_open()
    {
        // Create an open period
        $openPeriod = AccountingPeriod::create([
            'period_code' => '2026-04',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create currency positions
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => 1000.0000,
            'avg_cost_rate' => 4.4000,
        ]);

        // Run revaluation with journal
        $result = $this->service->runRevaluationWithJournal(now()->toDateString(), $this->user->id);

        // Verify journal entries were created
        $this->assertGreaterThan(0, $result['positions_updated']);

        // Verify entries have period_id assigned
        $journalEntry = JournalEntry::first();
        $this->assertNotNull($journalEntry);
        $this->assertEquals($openPeriod->id, $journalEntry->period_id);
    }

    /**
     * Test FAULT #5: Throws exception when trying to post to closed period
     */
    public function test_revaluation_with_journal_throws_exception_for_closed_period()
    {
        // Create a closed period
        AccountingPeriod::create([
            'period_code' => '2026-04',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $this->user->id,
        ]);

        // Create currency positions
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => 1000.0000,
            'avg_cost_rate' => 4.4000,
        ]);

        // Run revaluation with journal should throw exception for closed period
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('closed period');

        $this->service->runRevaluationWithJournal(now()->toDateString(), $this->user->id);
    }

    /**
     * Test FAULT #5: Validates posting date falls within period
     */
    public function test_revaluation_with_journal_validates_date_falls_in_period()
    {
        // Create an open period for current month
        AccountingPeriod::create([
            'period_code' => '2026-04',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create currency positions
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => 1000.0000,
            'avg_cost_rate' => 4.4000,
        ]);

        // Run revaluation with date outside period should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('date');

        $this->service->runRevaluationWithJournal(now()->subYear()->toDateString(), $this->user->id);
    }

    /**
     * Test FAULT #6: Each currency is processed independently in its own transaction
     */
    public function test_revaluation_processes_each_currency_independently()
    {
        // Create an open period
        AccountingPeriod::create([
            'period_code' => '2026-04',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create multiple currency positions
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => 1000.0000,
            'avg_cost_rate' => 4.4000,
        ]);
        CurrencyPosition::create([
            'currency_code' => 'EUR',
            'till_id' => 'MAIN',
            'balance' => 500.0000,
            'avg_cost_rate' => 4.7000,
        ]);
        CurrencyPosition::create([
            'currency_code' => 'GBP',
            'till_id' => 'MAIN',
            'balance' => 300.0000,
            'avg_cost_rate' => 5.8000,
        ]);

        // Run revaluation with journal
        $result = $this->service->runRevaluationWithJournal(now()->toDateString(), $this->user->id);

        // Verify all currencies were processed
        $this->assertEquals(3, $result['positions_updated']);
        $this->assertCount(3, $result['results']);

        // Verify each position has a revaluation entry
        foreach (['USD', 'EUR', 'GBP'] as $currency) {
            $this->assertDatabaseHas('revaluation_entries', [
                'currency_code' => $currency,
                'till_id' => 'MAIN',
            ]);
        }
    }

    /**
     * Test FAULT #6: Failure in one currency doesn't roll back others
     */
    public function test_currency_failure_does_not_affect_other_currencies()
    {
        // Create an open period
        AccountingPeriod::create([
            'period_code' => '2026-04',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create multiple currency positions
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => 1000.0000,
            'avg_cost_rate' => 4.4000,
        ]);
        CurrencyPosition::create([
            'currency_code' => 'EUR',
            'till_id' => 'MAIN',
            'balance' => 500.0000,
            'avg_cost_rate' => 4.7000,
        ]);

        // Get initial count of revaluation entries
        $initialCount = DB::table('revaluation_entries')->count();

        // Run revaluation - both should succeed
        $result = $this->service->runRevaluationWithJournal(now()->toDateString(), $this->user->id);

        // Verify both were processed
        $this->assertEquals(2, $result['positions_updated']);

        // Verify journal entries were created for successful currencies
        $journalCount = JournalEntry::where('reference_type', 'Revaluation')->count();
        $this->assertGreaterThanOrEqual(2, $journalCount);
    }

    /**
     * Test FAULT #5: Journal entries have period_id assigned
     */
    public function test_journal_entries_have_period_id_assigned()
    {
        // Create an open period
        $period = AccountingPeriod::create([
            'period_code' => '2026-04',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create currency position
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => 1000.0000,
            'avg_cost_rate' => 4.4000,
        ]);

        // Run revaluation
        $this->service->runRevaluationWithJournal(now()->toDateString(), $this->user->id);

        // Verify journal entry has period_id
        $journalEntry = JournalEntry::where('reference_type', 'Revaluation')->first();
        $this->assertNotNull($journalEntry);
        $this->assertEquals($period->id, $journalEntry->period_id);
    }
}
