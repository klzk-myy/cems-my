<?php

namespace Tests\Feature\Pos;

use App\Models\Counter;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\User;
use App\Modules\Pos\Models\PosDailyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Counter $counter;

    protected Currency $currency;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->counter = Counter::factory()->create(['status' => 'active']);
        $this->currency = Currency::factory()->create(['code' => 'USD', 'name' => 'US Dollar']);
        $this->customer = Customer::factory()->create([
            'full_name' => 'Test Customer',
            'risk_rating' => 'Low',
        ]);
    }

    public function test_complete_rate_setting_workflow(): void
    {
        $response = $this->actingAs($this->user)->postJson('/pos/rates/set', [
            'rates' => [
                'USD' => ['buy' => 4.6500, 'sell' => 4.7500, 'mid' => 4.7000],
                'EUR' => ['buy' => 5.0500, 'sell' => 5.1500, 'mid' => 5.1000],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pos_daily_rates', ['currency_code' => 'USD', 'buy_rate' => 4.6500]);
        $this->assertDatabaseHas('pos_daily_rates', ['currency_code' => 'EUR', 'buy_rate' => 5.0500]);
    }

    public function test_copy_yesterday_rates_workflow(): void
    {
        PosDailyRate::create([
            'rate_date' => now()->subDay()->toDateString(),
            'currency_code' => 'USD',
            'buy_rate' => 4.6500,
            'sell_rate' => 4.7500,
            'mid_rate' => 4.7000,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/pos/rates/copy-yesterday');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue(
            PosDailyRate::where('currency_code', 'USD')
                ->whereDate('rate_date', now()->toDateString())
                ->exists()
        );
    }

    public function test_get_today_rates_returns_correct_structure(): void
    {
        PosDailyRate::create([
            'rate_date' => now()->toDateString(),
            'currency_code' => 'USD',
            'buy_rate' => 4.6500,
            'sell_rate' => 4.7500,
            'mid_rate' => 4.7000,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/pos/rates/today');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'date',
                'rates' => ['USD' => ['buy', 'sell', 'mid']],
                'last_updated',
                'updated_by',
            ]);
    }

    public function test_transaction_quote_calculation(): void
    {
        PosDailyRate::create([
            'rate_date' => now()->toDateString(),
            'currency_code' => 'USD',
            'buy_rate' => 4.6500,
            'sell_rate' => 4.7500,
            'mid_rate' => 4.7000,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson('/pos/transactions/quote', [
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => 1000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'quote' => ['amount_local', 'rate', 'cdd_level'],
                'validation' => ['errors', 'warnings'],
            ]);
    }

    public function test_inventory_aggregate_returns_correct_structure(): void
    {
        CurrencyPosition::create([
            'till_id' => $this->counter->code,
            'currency_code' => 'USD',
            'balance' => 10000.00,
            'avg_cost_rate' => 4.5000,
        ]);

        $response = $this->actingAs($this->user)->getJson('/pos/inventory/aggregate');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'inventory',
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_low_stock_returns_currencies_below_threshold(): void
    {
        CurrencyPosition::create([
            'till_id' => $this->counter->code,
            'currency_code' => 'USD',
            'balance' => 5000.00,
            'weighted_avg_cost' => 4.5000,
        ]);

        $response = $this->actingAs($this->user)->getJson('/pos/inventory/low-stock?threshold=10000');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'low_stock',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('low_stock'));
    }

    public function test_eod_variance_calculation(): void
    {
        CurrencyPosition::create([
            'till_id' => $this->counter->code,
            'currency_code' => 'USD',
            'balance' => 10000.00,
            'avg_cost_rate' => 4.5000,
        ]);

        $response = $this->actingAs($this->user)->postJson('/pos/inventory/eod', [
            'counter_id' => $this->counter->code,
            'physical_counts' => [
                ['currency_code' => 'USD', 'amount' => 10400],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'variances',
                'requires_manager_approval',
                'requires_notes',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertFalse($response->json('requires_manager_approval'));
        $this->assertTrue($response->json('requires_notes'));
    }

    public function test_unauthenticated_access_blocked(): void
    {
        $response = $this->getJson('/pos/rates/today');
        $response->assertStatus(401);

        $response = $this->getJson('/pos/transactions/create');
        $response->assertStatus(401);

        $response = $this->getJson('/pos/inventory');
        $response->assertStatus(401);
    }

    public function test_rate_history_returns_historical_data(): void
    {
        for ($i = 0; $i < 3; $i++) {
            PosDailyRate::create([
                'rate_date' => now()->subDays($i)->toDateString(),
                'currency_code' => 'USD',
                'buy_rate' => 4.6500 + ($i * 0.01),
                'sell_rate' => 4.7500 + ($i * 0.01),
                'mid_rate' => 4.7000 + ($i * 0.01),
                'is_active' => true,
                'created_by' => $this->user->id,
            ]);
        }

        $response = $this->actingAs($this->user)->getJson('/pos/rates/history?days=7');

        $response->assertStatus(200)
            ->assertJsonStructure(['history']);

        $this->assertNotEmpty($response->json('history'));
    }
}
