<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Customer $customer;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        CurrencyFactory::resetCounter();
        $this->user = User::factory()->create(['role' => 'teller']);
        $this->customer = Customer::factory()->create();
        // Use a test currency code to avoid conflicts
        $this->currency = Currency::firstOrCreate(
            ['code' => 'TST'],
            ['name' => 'Test Currency', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]
        );
    }

    public function test_customer_history_displays_correct_statistics()
    {
        $this->actingAs($this->user);

        // Create transactions
        Transaction::factory()->count(5)->create([
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'TST',
            'amount_local' => 1000,
            'amount_foreign' => 220,
            'status' => 'Completed',
        ]);

        Transaction::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'TST',
            'amount_local' => 800,
            'amount_foreign' => 180,
            'status' => 'Completed',
        ]);

        $response = $this->get(route('customers.history', $this->customer));

        $response->assertStatus(200);
        $response->assertViewHas('stats');

        $stats = $response->viewData('stats');

        $this->assertEquals(8, $stats['total_count']); // 5 buy + 3 sell
        $this->assertEquals(5000, $stats['buy_volume']); // 5 * 1000
        $this->assertEquals(2400, $stats['sell_volume']); // 3 * 800
        $this->assertEquals(7400, $stats['total_volume']); // 5000 + 2400
        $this->assertEquals(925, $stats['avg_transaction']); // 7400 / 8
    }

    public function test_customer_history_shows_paginated_transactions()
    {
        $this->actingAs($this->user);

        // Create 25 transactions
        Transaction::factory()->count(25)->create([
            'customer_id' => $this->customer->id,
            'currency_code' => 'TST',
            'status' => 'Completed',
        ]);

        $response = $this->get(route('customers.history', $this->customer));

        $response->assertStatus(200);
        $response->assertViewHas('transactions');

        $transactions = $response->viewData('transactions');
        $this->assertEquals(20, $transactions->perPage()); // Default pagination
    }

    public function test_customer_history_calculates_first_and_last_transaction_dates()
    {
        $this->actingAs($this->user);

        // Create transactions at different dates
        Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'currency_code' => 'TST',
            'created_at' => now()->subDays(30),
            'status' => 'Completed',
        ]);

        Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'currency_code' => 'TST',
            'created_at' => now()->subDay(),
            'status' => 'Completed',
        ]);

        $response = $this->get(route('customers.history', $this->customer));

        $stats = $response->viewData('stats');

        $this->assertNotNull($stats['first_transaction']);
        $this->assertNotNull($stats['last_transaction']);
    }

    public function test_customer_history_export_returns_csv()
    {
        $this->actingAs($this->user);

        Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'TST',
            'amount_local' => 1000,
            'amount_foreign' => 220,
            'status' => 'Completed',
        ]);

        $response = $this->get(route('customers.export', $this->customer));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_customer_history_with_no_transactions_shows_zero_stats()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('customers.history', $this->customer));

        $stats = $response->viewData('stats');

        $this->assertEquals(0, $stats['total_count']);
        $this->assertEquals(0, $stats['buy_volume']);
        $this->assertEquals(0, $stats['sell_volume']);
        $this->assertEquals(0, $stats['total_volume']);
        $this->assertEquals(0, $stats['avg_transaction']);
    }

    public function test_customer_history_returns_monthly_chart_data()
    {
        $this->actingAs($this->user);

        // Create transactions in different months
        Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'TST',
            'created_at' => now()->subMonths(2),
            'amount_local' => 1000,
            'status' => 'Completed',
        ]);

        Transaction::factory()->create([
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'TST',
            'created_at' => now()->subMonths(1),
            'amount_local' => 500,
            'status' => 'Completed',
        ]);

        $response = $this->get(route('customers.history', $this->customer));

        $response->assertViewHas('chartLabels');
        $response->assertViewHas('chartBuyData');
        $response->assertViewHas('chartSellData');
    }
}
