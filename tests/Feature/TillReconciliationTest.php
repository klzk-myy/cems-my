<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TillReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected Currency $currency;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        CurrencyFactory::resetCounter();
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->currency = Currency::firstOrCreate(
            ['code' => 'TRC'],
            ['name' => 'Test Reconciliation Currency', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]
        );
        $this->customer = Customer::factory()->create();
    }

    public function test_reconciliation_calculates_expected_closing_correctly()
    {
        $this->actingAs($this->manager);

        // Create till with opening balance
        $tillBalance = TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'TRC',
            'opening_balance' => 10000,
            'date' => today(),
            'opened_by' => $this->manager->id,
        ]);

        // Create buy transactions (increasing balance)
        Transaction::factory()->create([
            'till_id' => 'TILL-001',
            'currency_code' => 'TRC',
            'type' => 'Buy',
            'amount_local' => 5000,
            'status' => 'Completed',
            'created_at' => today(),
        ]);

        // Create sell transactions (decreasing balance)
        Transaction::factory()->create([
            'till_id' => 'TILL-001',
            'currency_code' => 'TRC',
            'type' => 'Sell',
            'amount_local' => 2000,
            'status' => 'Completed',
            'created_at' => today(),
        ]);

        $response = $this->get(route('stock-cash.reconciliation', [
            'date' => today()->toDateString(),
            'till_id' => 'TILL-001',
        ]));

        $response->assertStatus(200);

        $reconciliation = $response->viewData('reconciliation');

        // Opening: 10000 + Purchases: 5000 - Sales: 2000 = Expected: 13000
        $this->assertEquals(10000, $reconciliation['opening_balance']);
        $this->assertEquals(5000, $reconciliation['purchases']['total']);
        $this->assertEquals(2000, $reconciliation['sales']['total']);
        $this->assertEquals(13000, $reconciliation['expected_closing']);
    }

    public function test_reconciliation_calculates_variance_correctly()
    {
        $this->actingAs($this->manager);

        // Create till with opening and closing balance
        $tillBalance = TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => 10000,
            'closing_balance' => 12800, // Actual is 200 less than expected
            'variance' => -200,
            'date' => today(),
            'opened_by' => $this->manager->id,
            'closed_by' => $this->manager->id,
            'closed_at' => now(),
        ]);

        // Create transactions
        Transaction::factory()->create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'type' => 'Buy',
            'amount_local' => 5000,
            'status' => 'Completed',
            'created_at' => today(),
        ]);

        Transaction::factory()->create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'type' => 'Sell',
            'amount_local' => 2000,
            'status' => 'Completed',
            'created_at' => today(),
        ]);

        $response = $this->get(route('stock-cash.reconciliation', [
            'date' => today()->toDateString(),
            'till_id' => 'TILL-001',
        ]));

        $reconciliation = $response->viewData('reconciliation');

        // Expected: 13000, Actual: 12800, Variance: -200
        $this->assertEquals(12800, $reconciliation['actual_closing']);
        $this->assertEquals(-200, $reconciliation['variance']);
        $this->assertTrue($reconciliation['is_closed']);
    }

    public function test_reconciliation_shows_null_variance_for_open_till()
    {
        $this->actingAs($this->manager);

        // Create till without closing
        $tillBalance = TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => 10000,
            'date' => today(),
            'opened_by' => $this->manager->id,
        ]);

        $response = $this->get(route('stock-cash.reconciliation', [
            'date' => today()->toDateString(),
            'till_id' => 'TILL-001',
        ]));

        $reconciliation = $response->viewData('reconciliation');

        $this->assertNull($reconciliation['actual_closing']);
        $this->assertNull($reconciliation['variance']);
        $this->assertFalse($reconciliation['is_closed']);
    }

    public function test_reconciliation_shows_transaction_details()
    {
        $this->actingAs($this->manager);

        $tillBalance = TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => 10000,
            'date' => today(),
            'opened_by' => $this->manager->id,
        ]);

        // Create 3 transactions
        Transaction::factory()->count(3)->create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'status' => 'Completed',
            'created_at' => today(),
        ]);

        $response = $this->get(route('stock-cash.reconciliation', [
            'date' => today()->toDateString(),
            'till_id' => 'TILL-001',
        ]));

        $transactions = $response->viewData('transactions');
        $summary = $response->viewData('summary');

        $this->assertCount(3, $transactions);
        $this->assertEquals(3, $summary['total_transactions']);
    }

    public function test_reconciliation_calculates_buy_and_sell_counts()
    {
        $this->actingAs($this->manager);

        $tillBalance = TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => 10000,
            'date' => today(),
            'opened_by' => $this->manager->id,
        ]);

        Transaction::factory()->count(5)->create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'type' => 'Buy',
            'status' => 'Completed',
            'created_at' => today(),
        ]);

        Transaction::factory()->count(3)->create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'type' => 'Sell',
            'status' => 'Completed',
            'created_at' => today(),
        ]);

        $response = $this->get(route('stock-cash.reconciliation', [
            'date' => today()->toDateString(),
            'till_id' => 'TILL-001',
        ]));

        $summary = $response->viewData('summary');

        $this->assertEquals(5, $summary['total_buy_count']);
        $this->assertEquals(3, $summary['total_sell_count']);
        $this->assertEquals(8, $summary['total_transactions']);
    }

    public function test_reconciliation_returns_error_for_nonexistent_till()
    {
        $this->actingAs($this->manager);

        $response = $this->get(route('stock-cash.reconciliation', [
            'date' => today()->toDateString(),
            'till_id' => 'NONEXISTENT',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_non_manager_cannot_access_reconciliation()
    {
        $user = User::factory()->create(['role' => 'teller']);
        $this->actingAs($user);

        $response = $this->get(route('stock-cash.reconciliation', [
            'date' => today()->toDateString(),
            'till_id' => 'TILL-001',
        ]));

        $response->assertStatus(403);
    }
}
