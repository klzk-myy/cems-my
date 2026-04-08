<?php

namespace Tests\Feature;

use App\Enums\CddLevel;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Transaction Workflow Tests
 *
 * Tests comprehensive transaction workflows including:
 * - Buy and sell transaction creation
 * - Approval flows for large transactions
 * - Cancellation and refund processing
 * - Stock validation for sell transactions
 */
class TransactionWorkflowTest extends TestCase
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

        // Create users with different roles
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => UserRole::Admin,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
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

        // Open till (use MAIN to match existing test patterns)
        $this->tillBalance = TillBalance::create([
            'till_id' => 'MAIN',
            'currency_code' => 'USD',
            'opening_balance' => '10000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);

        // Create accounting period for journal entries
        AccountingPeriod::create([
            'period_code' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create required chart of accounts for journal entries
        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash - MYR', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Inventory', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Gain on FX', 'account_type' => 'Revenue', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '6000'],
            ['account_name' => 'Loss on FX', 'account_type' => 'Expense', 'is_active' => true]
        );
    }

    /**
     * Test creating a buy transaction completes successfully
     */
    public function test_creating_buy_transaction_completes_successfully(): void
    {
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => 'USD',
            'status' => TransactionStatus::Completed->value,
        ]);

        // Verify transaction creates audit log
        $transaction = Transaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->tellerUser->id,
            'action' => 'transaction_created',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
        ]);

        // Verify currency position is updated (stock increases on buy)
        $this->assertDatabaseHas('currency_positions', [
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
        ]);
    }

    /**
     * Test creating a sell transaction with sufficient stock completes successfully
     */
    public function test_creating_sell_transaction_with_sufficient_stock_completes(): void
    {
        // First create a position with stock
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
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
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => TransactionType::Sell->value,
            'currency_code' => 'USD',
            'status' => TransactionStatus::Completed->value,
        ]);

        // Verify currency position is decreased (stock decreases on sell)
        $position = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'MAIN')
            ->first();
        $this->assertNotNull($position);
        $this->assertEquals('4500.0000', $position->balance);
    }

    /**
     * Test transaction approval flow for pending transactions
     */
    public function test_pending_transaction_approval_flow(): void
    {
        // Create a pending transaction (large amount >= RM 50,000)
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => TransactionStatus::Pending,
            'cdd_level' => CddLevel::Enhanced,
            'version' => 0,
        ]);

        // Verify initial status is Pending
        $this->assertEquals(TransactionStatus::Pending, $transaction->status);

        // Manager approves the transaction
        $response = $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $response->assertRedirect();

        // Verify status changed to Completed
        $transaction->refresh();
        $this->assertEquals(TransactionStatus::Completed, $transaction->status);
        $this->assertEquals($this->managerUser->id, $transaction->approved_by);
        $this->assertNotNull($transaction->approved_at);

        // Verify audit log created
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->managerUser->id,
            'action' => 'transaction_approved',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
        ]);

        // Verify journal entries created
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => 'Transaction',
            'reference_id' => $transaction->id,
        ]);
    }

    /**
     * Test transaction cancellation by manager
     */
    public function test_manager_can_cancel_transaction(): void
    {
        // Create currency position for the transaction
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '5000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        // Create a completed transaction
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'amount_local' => '4720.00',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => TransactionStatus::Completed,
            'cdd_level' => CddLevel::Simplified,
            'created_at' => now(),
        ]);

        // Manager accesses cancel form
        $this->actingAs($this->managerUser);

        $response = $this->get("/transactions/{$transaction->id}/cancel");

        $response->assertStatus(200);
        $response->assertSee('Cancel Transaction');

        // Manager submits cancellation
        $response = $this->post("/transactions/{$transaction->id}/cancel", [
            'cancellation_reason' => 'Customer requested cancellation due to change of plans',
            'confirm_understanding' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Transaction reversed successfully. Refund transaction created.');

        // Verify transaction is reversed
        $transaction->refresh();
        $this->assertEquals(TransactionStatus::Reversed, $transaction->status);
        $this->assertNotNull($transaction->cancelled_at);
        $this->assertEquals($this->managerUser->id, $transaction->cancelled_by);
        $this->assertEquals('Customer requested cancellation due to change of plans', $transaction->cancellation_reason);

        // Verify refund transaction was created
        $refundTransaction = Transaction::where('original_transaction_id', $transaction->id)->first();
        $this->assertNotNull($refundTransaction);
        $this->assertTrue((bool) $refundTransaction->is_refund);
        $this->assertEquals(TransactionType::Sell->value, $refundTransaction->type->value); // Reverse of Buy
        $this->assertEquals($transaction->amount_foreign, $refundTransaction->amount_foreign);
    }

    /**
     * Test refund transaction creation maintains correct amounts
     */
    public function test_refund_transaction_maintains_correct_amounts(): void
    {
        // Create currency position for the transaction
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '5000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        // Create a completed buy transaction
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '2000',
            'amount_local' => '9440.00',
            'rate' => '4.7200',
            'purpose' => 'Business Travel',
            'source_of_funds' => 'Company Funds',
            'status' => TransactionStatus::Completed,
            'cdd_level' => CddLevel::Enhanced,
            'created_at' => now(),
        ]);

        // Manager cancels the transaction
        $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/cancel", [
                'cancellation_reason' => 'Business plans changed',
                'confirm_understanding' => '1',
            ]);

        // Verify refund transaction
        $refundTransaction = Transaction::where('original_transaction_id', $transaction->id)->first();
        $this->assertNotNull($refundTransaction);
        $this->assertEquals($transaction->amount_foreign, $refundTransaction->amount_foreign);
        $this->assertEquals($transaction->amount_local, $refundTransaction->amount_local);
        $this->assertEquals($transaction->rate, $refundTransaction->rate);
        $this->assertEquals('Refund: Business Travel', $refundTransaction->purpose);
        $this->assertEquals('Refund', $refundTransaction->source_of_funds);
    }

    /**
     * Test large transaction (>= RM 50,000) requires manager approval
     */
    public function test_large_transaction_requires_manager_approval(): void
    {
        // Submit a large transaction (>= RM 50,000 at rate 4.72 = 11000+ USD)
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '11000', // RM 51,920 at 4.72
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning', 'Transaction created and pending manager approval (≥ RM 50,000).');

        // Verify transaction is Pending
        $transaction = Transaction::where('customer_id', $this->customer->id)
            ->where('type', TransactionType::Buy)
            ->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionStatus::Pending, $transaction->status);

        // Teller cannot approve - requires manager
        $tellerAttempt = $this->actingAs($this->tellerUser)
            ->post("/transactions/{$transaction->id}/approve");
        $tellerAttempt->assertStatus(403);

        // Manager can approve
        $managerResponse = $this->actingAs($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");
        $managerResponse->assertRedirect();

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::Completed, $transaction->status);
    }

    /**
     * Test sell transaction fails with insufficient stock
     */
    public function test_sell_fails_with_insufficient_stock(): void
    {
        // No position created - sell should fail
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', function ($value) {
            return str_contains($value, 'Insufficient stock');
        });

        // Verify no transaction was created
        $this->assertDatabaseMissing('transactions', [
            'customer_id' => $this->customer->id,
            'type' => TransactionType::Sell->value,
            'currency_code' => 'USD',
        ]);
    }

    /**
     * Test sell transaction with insufficient remaining stock fails
     */
    public function test_sell_fails_with_insufficient_remaining_stock(): void
    {
        // Create a position with only 500 USD
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '500',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        // Try to sell 1000 USD (more than available)
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', function ($value) {
            return str_contains($value, 'Insufficient stock');
        });

        // Verify no transaction was created
        $this->assertDatabaseMissing('transactions', [
            'customer_id' => $this->customer->id,
            'type' => TransactionType::Sell->value,
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
        ]);
    }

    /**
     * Test small transaction below threshold completes immediately
     */
    public function test_small_transaction_completes_immediately(): void
    {
        // Submit a small transaction (RM 4,720 at 4.72)
        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000', // RM 4,720
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Transaction completed successfully.', session('success'));

        // Verify transaction is Completed (not Pending)
        $transaction = Transaction::where('customer_id', $this->customer->id)
            ->where('type', TransactionType::Buy)
            ->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionStatus::Completed, $transaction->status);
    }

    /**
     * Test teller cannot approve transactions
     */
    public function test_teller_cannot_approve_transactions(): void
    {
        // Create a pending transaction
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '11000',
            'amount_local' => '51920.00',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'status' => TransactionStatus::Pending,
            'cdd_level' => CddLevel::Enhanced,
            'version' => 0,
        ]);

        // Teller attempts to approve
        $response = $this->actingAs($this->tellerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $response->assertStatus(403);

        // Verify status unchanged
        $transaction->refresh();
        $this->assertEquals(TransactionStatus::Pending, $transaction->status);
    }

    /**
     * Test transaction creates journal entries on completion
     */
    public function test_transaction_creates_journal_entries_on_completion(): void
    {
        // Create a position first so sell can succeed
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '5000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        // Create a sell transaction through store (which creates journal entries)
        $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '500',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        // Verify journal entries were created
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => 'Transaction',
        ]);

        $journalEntry = JournalEntry::where('reference_type', 'Transaction')->first();
        $this->assertNotNull($journalEntry);
        $this->assertTrue($journalEntry->lines->count() >= 2); // At least 2 lines for double-entry
    }

    /**
     * Test cancelled transaction cannot be cancelled again
     */
    public function test_cancelled_transaction_cannot_be_cancelled_again(): void
    {
        // Create and cancel a transaction
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'amount_local' => '4720.00',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => TransactionStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $this->managerUser->id,
            'cancellation_reason' => 'Already cancelled',
            'cdd_level' => CddLevel::Simplified,
        ]);

        // Try to cancel again
        $response = $this->actingAs($this->managerUser)
            ->get("/transactions/{$transaction->id}/cancel");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This transaction cannot be cancelled in its current state.');
    }

    /**
     * Test transaction requires till to be open
     */
    public function test_transaction_fails_when_till_is_closed(): void
    {
        // Close the till
        $this->tillBalance->update(['closed_at' => now()]);

        $response = $this->actingAs($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', 'Till is not open for this currency. Please open the till first.');
    }
}
