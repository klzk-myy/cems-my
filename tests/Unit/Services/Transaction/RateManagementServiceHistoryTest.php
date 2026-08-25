<?php

namespace Tests\Unit\Services\Transaction;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\ExchangeRateHistory;
use App\Services\Transaction\RateManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateManagementServiceHistoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function returns_history_ordered_by_effective_date_descending(): void
    {
        $branch = Branch::factory()->create();
        $oldest = ExchangeRateHistory::factory()->create([
            'currency_code' => 'USD',
            'branch_id' => $branch->id,
            'effective_date' => now()->subDays(5)->toDateString(),
        ]);
        $newest = ExchangeRateHistory::factory()->create([
            'currency_code' => 'USD',
            'branch_id' => $branch->id,
            'effective_date' => now()->subDay()->toDateString(),
        ]);
        $middle = ExchangeRateHistory::factory()->create([
            'currency_code' => 'USD',
            'branch_id' => $branch->id,
            'effective_date' => now()->subDays(3)->toDateString(),
        ]);

        $service = app(RateManagementService::class);
        $histories = $service->getRateHistory('USD', 30, $branch->id);

        $this->assertCount(3, $histories);
        $this->assertTrue($histories->first()->is($newest));
        $this->assertTrue($histories->skip(1)->first()->is($middle));
        $this->assertTrue($histories->skip(2)->first()->is($oldest));
    }

    #[Test]
    public function filters_by_branch_when_provided(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        ExchangeRateHistory::factory()->create([
            'currency_code' => 'USD',
            'branch_id' => $branchA->id,
            'effective_date' => now()->subDays(5)->toDateString(),
        ]);
        ExchangeRateHistory::factory()->create([
            'currency_code' => 'USD',
            'branch_id' => $branchB->id,
            'effective_date' => now()->subDays(5)->toDateString(),
        ]);

        $service = app(RateManagementService::class);
        $histories = $service->getRateHistory('USD', 30, $branchA->id);

        $this->assertCount(1, $histories);
        $this->assertEquals($branchA->id, $histories->first()->branch_id);
    }

    #[Test]
    public function are_all_rates_set_reports_missing_currencies(): void
    {
        ExchangeRate::factory()->create(['currency_code' => 'USD']);
        ExchangeRate::factory()->create(['currency_code' => 'EUR']);

        $service = app(RateManagementService::class);
        $result = $service->areAllRatesSet(['USD', 'EUR', 'GBP']);

        $this->assertFalse($result['all_set']);
        $this->assertSame(['GBP'], $result['missing']);
    }

    #[Test]
    public function are_all_rates_set_is_true_when_all_present(): void
    {
        ExchangeRate::factory()->create(['currency_code' => 'USD']);
        ExchangeRate::factory()->create(['currency_code' => 'EUR']);

        $service = app(RateManagementService::class);
        $result = $service->areAllRatesSet(['USD', 'EUR']);

        $this->assertTrue($result['all_set']);
        $this->assertSame([], $result['missing']);
    }

    #[Test]
    public function are_all_rates_set_uses_a_single_query_not_one_per_currency(): void
    {
        Currency::factory()->create(['code' => 'JPY']);
        ExchangeRate::factory()->create(['currency_code' => 'USD']);
        ExchangeRate::factory()->create(['currency_code' => 'EUR']);
        ExchangeRate::factory()->create(['currency_code' => 'GBP']);
        ExchangeRate::factory()->create(['currency_code' => 'JPY']);

        DB::enableQueryLog();
        app(RateManagementService::class)->areAllRatesSet(['USD', 'EUR', 'GBP', 'JPY']);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries, 'areAllRatesSet must issue a single query (N+1 regression guard)');
    }

    #[Test]
    public function respects_days_window(): void
    {
        $branch = Branch::factory()->create();

        $insideWindow = ExchangeRateHistory::factory()->create([
            'currency_code' => 'USD',
            'branch_id' => $branch->id,
            'effective_date' => now()->subDays(5)->toDateString(),
        ]);
        ExchangeRateHistory::factory()->create([
            'currency_code' => 'USD',
            'branch_id' => $branch->id,
            'effective_date' => now()->subDays(10)->toDateString(),
        ]);
        ExchangeRateHistory::factory()->create([
            'currency_code' => 'USD',
            'branch_id' => $branch->id,
            'effective_date' => now()->subDays(31)->toDateString(),
        ]);

        $service = app(RateManagementService::class);
        $histories = $service->getRateHistory('USD', 7, $branch->id);

        $this->assertCount(1, $histories);
        $this->assertTrue($histories->first()->is($insideWindow));
    }
}
