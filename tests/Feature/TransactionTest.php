<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $tellerUser;

    protected User $managerUser;

    protected Customer $customer;

    protected Currency $currency;

    protected TillBalance $tillBalance;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => 'admin',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => 'manager',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        // Create currency
        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'rate_buy' => 4.7200,
                'rate_sell' => 4.7500,
                'is_active' => true,
            ]
        );

        // Create customer
        $this->customer = Customer::create([
            'full_name' => 'Test Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789012'),
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('123 Test Street'),
            'contact_number_encrypted' => encrypt('0123456789'),
            'email' => 'customer@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'Low',
        ]);

        // Open till
        $this->tillBalance = TillBalance::create([
            'till_id' => 'TILL-001',
            'currency_code' => 'USD',
            'opening_balance' => '10000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);
    }

    /**
     * Test teller can access transaction creation form
     */
    public function test_teller_can_access_transaction_create(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/transactions/create');

        $response->assertStatus(200);
        $response->assertSee('Create New Transaction');
        $response->assertSee('USD');
    }

    /**
     * Test teller can create buy transaction
     */
    public function test_teller_can_create_buy_transaction(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'status' => 'Completed',
        ]);
    }

    /**
     * Test teller can create sell transaction with sufficient stock
     */
    public function test_teller_can_create_sell_transaction(): void
    {
        // First create a position with stock
        $position = CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'TILL-001',
            'balance' => '5000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '500',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'TILL-001',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'status' => 'Completed',
        ]);
    }

    /**
     * Test sell transaction fails with insufficient stock
     */
    public function test_sell_fails_with_insufficient_stock(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'TILL-001',
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', function ($value) {
            return str_contains($value, 'Insufficient stock');
        });
    }

    /**
     * Test transaction fails if till not open
     */
    public function test_transaction_fails_if_till_not_open(): void
    {
        // Close the till
        $this->tillBalance->update(['closed_at' => now()]);

        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $response->assertSessionHas('error');
    }

    /**
     * Test large transaction requires approval (≥ RM 50,000)
     */
    public function test_large_transaction_requires_approval(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000', // > RM 50,000 at 4.72
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'till_id' => 'TILL-001',
        ]);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'status' => 'Pending',
        ]);
    }

    /**
     * Test manager can approve pending transaction
     */
    public function test_manager_can_approve_transaction(): void
    {
        // Create pending transaction
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => 'Pending',
            'cdd_level' => 'Enhanced',
        ]);

        $response = $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $response->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('Completed', $transaction->status);
        $this->assertNotNull($transaction->approved_by);
        $this->assertNotNull($transaction->approved_at);
    }

    /**
     * Test teller cannot approve transaction
     */
    public function test_teller_cannot_approve_transaction(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => 'Pending',
            'cdd_level' => 'Enhanced',
        ]);

        $response = $this->actingAs($this->tellerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $response->assertStatus(403);
    }

    /**
     * Test transaction creates audit log
     */
    public function test_transaction_creates_audit_log(): void
    {
        $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $transaction = Transaction::where('customer_id', $this->customer->id)->first();

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->tellerUser->id,
            'action' => 'transaction_created',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
        ]);
    }

    /**
     * Test currency position updates on buy
     */
    public function test_buy_updates_currency_position(): void
    {
        $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $this->assertDatabaseHas('currency_positions', [
            'currency_code' => 'USD',
            'till_id' => 'TILL-001',
            'balance' => '1000.0000',
        ]);
    }

    /**
     * Test currency position updates on sell
     */
    public function test_sell_updates_currency_position(): void
    {
        // Setup position
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'TILL-001',
            'balance' => '2000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '500',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'TILL-001',
        ]);

        $position = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'TILL-001')
            ->first();

        $this->assertEquals('1500.0000', $position->balance);
    }

    /**
     * Test can view transaction details
     */
    public function test_can_view_transaction_details(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'amount_local' => '472',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => 'Completed',
            'cdd_level' => 'Simplified',
        ]);

        $response = $this->actingAs($this->tellerUser)
            ->get("/transactions/{$transaction->id}");

        $response->assertStatus(200);
        $response->assertSee('Transaction #'.$transaction->id);
        $response->assertSee('Test Customer');
        $response->assertSee('USD');
    }

    /**
     * Test transaction list loads
     */
    public function test_can_view_transaction_list(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('Transaction History');
    }

    /**
     * Test transaction requires valid customer
     */
    public function test_transaction_requires_valid_customer(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => 99999,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $response->assertSessionHasErrors('customer_id');
    }

    /**
     * Test transaction requires valid currency
     */
    public function test_transaction_requires_valid_currency(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'XXX',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $response->assertSessionHasErrors('currency_code');
    }

    /**
     * Test transaction requires positive amount
     */
    public function test_transaction_requires_positive_amount(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '-100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'TILL-001',
        ]);

        $response->assertSessionHasErrors('amount_foreign');
    }

    /**
     * Test approval creates journal entries
     */
    public function test_approval_creates_journal_entries(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => 'Pending',
            'cdd_level' => 'Enhanced',
        ]);

        $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->managerUser->id,
            'action' => 'transaction_approved',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
        ]);

        $this->assertDatabaseHas('system_logs', [
            'action' => 'journal_entry',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
        ]);
    }
}
