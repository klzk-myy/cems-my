<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Real-World Transaction Workflow Test
 *
 * Tests the complete transaction lifecycle as it would happen in production:
 * 1. Morning till opening
 * 2. Customer registration
 * 3. Currency purchase (Buy)
 * 4. Currency sale (Sell)
 * 5. Large transaction approval
 * 6. End of day till closing
 * 7. Compliance monitoring
 * 8. Reporting
 */
class RealWorldTransactionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * TEST: Complete Daily Workflow
     * Simulates a real day at an MSB counter
     */
    public function test_complete_daily_workflow()
    {
        // ============ SETUP ============
        // Create users
        $teller = User::factory()->create(['role' => 'teller', 'username' => 'teller1']);
        $manager = User::factory()->create(['role' => 'manager', 'username' => 'manager1']);

        // Create currency
        $currency = Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
        ]);

        // ============ STEP 1: Morning Till Opening ============
        // Manager opens the till (only managers/admins can open tills)
        $this->actingAs($manager)
            ->post(route('stock-cash.open'), [
                'till_id' => 'TILL-001',
                'currency_code' => 'USD',
                'opening_balance' => 10000,
                'opened_by' => $manager->id,
            ])
            ->assertRedirect();

        $tillBalance = TillBalance::where('till_id', 'TILL-001')
            ->where('currency_code', 'USD')
            ->whereDate('date', today())
            ->first();

        $this->assertNotNull($tillBalance);
        $this->assertEquals(10000, $tillBalance->opening_balance);

        // ============ STEP 2: Customer Registration ============
        $customer = Customer::factory()->create([
            'full_name' => 'Ahmad Bin Abdullah',
            'id_type' => 'MyKad',
            'id_number_encrypted' => '850612-14-5289',
            'nationality' => 'Malaysian',
            'phone' => '012-3456789',
            'email' => 'ahmad@example.com',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'full_name' => 'Ahmad Bin Abdullah',
        ]);

        // ============ STEP 3: Currency Purchase (Buy) ============
        $response = $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'amount_foreign' => 1000,
                'rate' => 4.72,
                'purpose' => 'Business Travel',
                'source_of_funds' => 'Salary',
                'till_id' => 'TILL-001',
            ]);

        $response->assertRedirect();

        // Get the transaction
        $buyTransaction = Transaction::where('customer_id', $customer->id)
            ->where('type', 'Buy')
            ->first();

        $this->assertNotNull($buyTransaction);
        $this->assertTrue($buyTransaction->status->isCompleted());
        $this->assertEquals(4720.00, $buyTransaction->amount_local); // 1000 * 4.72

        // Check stock updated
        $position = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'TILL-001')
            ->first();

        $this->assertNotNull($position);
        $this->assertEquals(1000, $position->balance); // Bought 1000 USD

        // ============ STEP 4: Currency Sale (Sell) ============
        $response = $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'type' => 'Sell',
                'currency_code' => 'USD',
                'amount_foreign' => 500,
                'rate' => 4.75,
                'purpose' => 'Personal Use',
                'source_of_funds' => 'Savings',
                'till_id' => 'TILL-001',
            ]);

        $response->assertRedirect();

        $sellTransaction = Transaction::where('customer_id', $customer->id)
            ->where('type', 'Sell')
            ->first();

        $this->assertNotNull($sellTransaction);
        $this->assertTrue($sellTransaction->status->isCompleted());
        $this->assertEquals(2375.00, $sellTransaction->amount_local); // 500 * 4.75

        // Check stock reduced
        $position->refresh();
        $this->assertEquals(500, $position->balance); // 1000 - 500 = 500 USD

        // ============ STEP 5: Large Transaction (Requires Approval) ============
        $response = $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'amount_foreign' => 12000, // Large amount
                'rate' => 4.72,
                'purpose' => 'Business Investment',
                'source_of_funds' => 'Business Revenue',
                'till_id' => 'TILL-001',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning'); // Should warn about pending approval

        $largeTransaction = Transaction::where('customer_id', $customer->id)
            ->where('amount_foreign', 12000)
            ->first();

        $this->assertNotNull($largeTransaction);
        $this->assertTrue($largeTransaction->status->isPending()); // Pending approval
        $this->assertEquals(56640.00, $largeTransaction->amount_local); // 12000 * 4.72

        // ============ STEP 6: Manager Approval ============
        $response = $this->actingAs($manager)
            ->post(route('transactions.approve', $largeTransaction));

        $response->assertRedirect();

        $largeTransaction->refresh();
        $this->assertTrue($largeTransaction->status->isCompleted());
        $this->assertEquals($manager->id, $largeTransaction->approved_by);
        $this->assertNotNull($largeTransaction->approved_at);

        // Check stock updated after approval
        $position->refresh();
        $this->assertEquals(12500, $position->balance); // 500 + 12000 = 12500 USD

        // ============ STEP 7: Verify Compliance Monitoring ============
        // Large transaction should trigger compliance monitoring
        $this->assertDatabaseHas('flagged_transactions', [
            'transaction_id' => $largeTransaction->id,
        ]);

        // ============ STEP 8: End of Day Till Closing ============
        // Manager closes the till (only managers/admins can close tills)
        $response = $this->actingAs($manager)
            ->post(route('stock-cash.close'), [
                'till_id' => 'TILL-001',
                'currency_code' => 'USD',
                'closing_balance' => 12500,
            ]);

        $response->assertRedirect();

        $tillBalance->refresh();
        $this->assertEquals(12500, $tillBalance->closing_balance);
        $this->assertNotNull($tillBalance->closed_at);

        // ============ STEP 9: Verify Daily Summary ============
        $dailyTransactions = Transaction::whereDate('created_at', today())->count();
        $this->assertEquals(3, $dailyTransactions);

        // Total buy volume
        $buyVolume = Transaction::where('type', 'Buy')
            ->whereDate('created_at', today())
            ->sum('amount_local');
        $this->assertEquals(61360.00, $buyVolume); // 4720 + 56640

        // Total sell volume
        $sellVolume = Transaction::where('type', 'Sell')
            ->whereDate('created_at', today())
            ->sum('amount_local');
        $this->assertEquals(2375.00, $sellVolume); // 2375

        // ============ VERIFICATION ============
        // All assertions passed!
        $this->assertTrue(true, 'Complete workflow executed successfully');
    }

    /**
     * TEST: Real-world edge cases
     */
    public function test_edge_cases()
    {
        $teller = User::factory()->create(['role' => 'teller']);
        $customer = Customer::factory()->create();

        // Open till
        TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'date' => today(),
            'opening_balance' => 1000,
            'opened_by' => $teller->id,
        ]);

        // Create position
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'TILL-001',
            'balance' => 1000,
            'avg_cost_rate' => 4.70,
        ]);

        // Edge Case 1: Try to sell more than available stock
        $response = $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'type' => 'Sell',
                'currency_code' => 'USD',
                'amount_foreign' => 5000, // More than available (1000)
                'rate' => 4.75,
                'purpose' => 'Travel',
                'source_of_funds' => 'Savings',
                'till_id' => 'TILL-001',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify no transaction was created
        $this->assertEquals(0, Transaction::where('type', 'Sell')->count());

        // Edge Case 2: Transaction without open till
        TillBalance::where('till_id', 'TILL-001')->delete();

        $response = $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'amount_foreign' => 100,
                'rate' => 4.72,
                'purpose' => 'Travel',
                'source_of_funds' => 'Savings',
                'till_id' => 'TILL-001',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Till is not open for this currency. Please open the till first.');
    }

    /**
     * TEST: Receipt generation workflow
     */
    public function test_receipt_generation_workflow()
    {
        $teller = User::factory()->create(['role' => 'teller']);
        $customer = Customer::factory()->create([
            'full_name' => 'Test Customer',
        ]);

        // Open till
        TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'date' => today(),
            'opening_balance' => 1000,
            'opened_by' => $teller->id,
        ]);

        // Create completed transaction
        $transaction = Transaction::create([
            'customer_id' => $customer->id,
            'user_id' => $teller->id,
            'till_id' => 'TILL-001',
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => 1000,
            'amount_local' => 4720.00,
            'rate' => 4.72,
            'purpose' => 'Business Travel',
            'source_of_funds' => 'Salary',
            'status' => 'Completed',
            'cdd_level' => 'Standard',
        ]);

        // Generate receipt
        $response = $this->actingAs($teller)
            ->get(route('transactions.receipt', $transaction));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * TEST: Transaction search and filtering
     */
    public function test_transaction_search_and_filtering()
    {
        $teller = User::factory()->create(['role' => 'teller']);
        $manager = User::factory()->create(['role' => 'manager']);

        $customer1 = Customer::factory()->create(['full_name' => 'John Doe']);
        $customer2 = Customer::factory()->create(['full_name' => 'Jane Smith']);

        // Create transactions
        Transaction::factory()->count(3)->create([
            'customer_id' => $customer1->id,
            'type' => 'Buy',
            'status' => 'Completed',
        ]);

        Transaction::factory()->count(2)->create([
            'customer_id' => $customer2->id,
            'type' => 'Sell',
            'status' => 'Completed',
        ]);

        // Test: Filter by customer
        $response = $this->actingAs($manager)
            ->get(route('transactions.index', ['customer_id' => $customer1->id]));

        $response->assertStatus(200);

        // Test: Filter by type
        $response = $this->actingAs($manager)
            ->get(route('transactions.index', ['type' => 'Sell']));

        $response->assertStatus(200);

        // Test: Filter by date range
        $response = $this->actingAs($manager)
            ->get(route('transactions.index', [
                'date_from' => today()->subDays(7)->toDateString(),
                'date_to' => today()->toDateString(),
            ]));

        $response->assertStatus(200);
    }

    /**
     * TEST: Daily transaction summary
     */
    public function test_daily_transaction_summary()
    {
        $manager = User::factory()->create(['role' => 'manager']);

        // Create multiple transactions today
        Transaction::factory()->count(5)->create([
            'type' => 'Buy',
            'status' => 'Completed',
            'created_at' => now(),
        ]);

        Transaction::factory()->count(3)->create([
            'type' => 'Sell',
            'status' => 'Completed',
            'created_at' => now(),
        ]);

        Transaction::factory()->count(2)->create([
            'type' => 'Buy',
            'status' => 'Pending',
            'created_at' => now(),
        ]);

        // View daily summary
        $response = $this->actingAs($manager)
            ->get(route('reports.msb2', ['date' => today()->toDateString()]));

        $response->assertStatus(200);

        // Check summary data
        $summary = $response->viewData('summary');
        $stats = $response->viewData('stats');

        $this->assertNotNull($summary);
        $this->assertNotNull($stats);
        $this->assertGreaterThan(0, $stats['total_transactions']);
    }
}
