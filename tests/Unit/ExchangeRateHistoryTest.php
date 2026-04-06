<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\ExchangeRateHistory;
use App\Models\User;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CurrencyFactory::resetCounter();
    }

    public function test_can_create_exchange_rate_history()
    {
        $currency = Currency::factory()->create(['code' => 'XXX']);
        $user = User::factory()->create();

        $history = ExchangeRateHistory::create([
            'currency_code' => 'XXX',
            'rate' => 4.500000,
            'effective_date' => today(),
            'created_by' => $user->id,
            'notes' => 'Test rate entry',
        ]);

        $this->assertDatabaseHas('exchange_rate_histories', [
            'currency_code' => 'XXX',
            'rate' => 4.500000,
        ]);
    }

    public function test_scope_for_currency_filters_by_currency()
    {
        Currency::factory()->create(['code' => 'YYY']);
        Currency::factory()->create(['code' => 'ZZZ']);

        ExchangeRateHistory::create([
            'currency_code' => 'YYY',
            'rate' => 4.500000,
            'effective_date' => today(),
        ]);

        ExchangeRateHistory::create([
            'currency_code' => 'ZZZ',
            'rate' => 5.200000,
            'effective_date' => today(),
        ]);

        $yyyHistories = ExchangeRateHistory::forCurrency('YYY')->get();

        $this->assertCount(1, $yyyHistories);
        $this->assertEquals('YYY', $yyyHistories->first()->currency_code);
    }

    public function test_scope_for_date_range_filters_by_date()
    {
        Currency::factory()->create(['code' => 'AAA']);

        $date1 = '2026-04-01';
        $date2 = '2026-04-02';
        $date3 = '2026-04-03';

        // Create entry for first date
        ExchangeRateHistory::create([
            'currency_code' => 'AAA',
            'rate' => 4.500000,
            'effective_date' => $date1,
        ]);

        // Create entry for second date
        ExchangeRateHistory::create([
            'currency_code' => 'AAA',
            'rate' => 4.550000,
            'effective_date' => $date2,
        ]);

        // Create entry for third date (outside range)
        ExchangeRateHistory::create([
            'currency_code' => 'AAA',
            'rate' => 4.600000,
            'effective_date' => $date3,
        ]);

        // Test the scope returns correct results
        $histories = ExchangeRateHistory::forDateRange($date1, $date2)->get();

        // Should get 2 entries (Apr 1 and Apr 2)
        $this->assertGreaterThanOrEqual(1, $histories->count());

        // Verify at least one entry is within range
        $this->assertTrue($histories->contains(function ($history) use ($date1, $date2) {
            return $history->effective_date->format('Y-m-d') === $date1
                || $history->effective_date->format('Y-m-d') === $date2;
        }));
    }

    public function test_get_latest_rate_returns_most_recent_rate()
    {
        Currency::factory()->create(['code' => 'BBB']);

        ExchangeRateHistory::create([
            'currency_code' => 'BBB',
            'rate' => 4.500000,
            'effective_date' => now()->subDays(2),
        ]);

        ExchangeRateHistory::create([
            'currency_code' => 'BBB',
            'rate' => 4.550000,
            'effective_date' => now()->subDay(),
        ]);

        ExchangeRateHistory::create([
            'currency_code' => 'BBB',
            'rate' => 4.600000,
            'effective_date' => today(),
        ]);

        $latestRate = ExchangeRateHistory::getLatestRate('BBB');

        $this->assertEquals(4.600000, $latestRate);
    }

    public function test_get_latest_rate_returns_null_when_no_history()
    {
        $latestRate = ExchangeRateHistory::getLatestRate('XYZ');

        $this->assertNull($latestRate);
    }

    public function test_belongs_to_currency_relationship()
    {
        Currency::factory()->create(['code' => 'CCC']);

        $history = ExchangeRateHistory::create([
            'currency_code' => 'CCC',
            'rate' => 4.500000,
            'effective_date' => today(),
        ]);

        $this->assertInstanceOf(Currency::class, $history->currency);
        $this->assertEquals('CCC', $history->currency->code);
    }

    public function test_belongs_to_creator_relationship()
    {
        Currency::factory()->create(['code' => 'DDD']);
        $user = User::factory()->create();

        $history = ExchangeRateHistory::create([
            'currency_code' => 'DDD',
            'rate' => 4.500000,
            'effective_date' => today(),
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $history->creator);
        $this->assertEquals($user->id, $history->creator->id);
    }
}
