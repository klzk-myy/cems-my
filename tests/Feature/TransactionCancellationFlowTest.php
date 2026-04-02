<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\SystemLog;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionCancellationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $managerUser;

    protected User $tellerUser1;

    protected User $tellerUser2;

    protected Customer $customer;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles (similar to TransactionTest.php)
        $this->adminUser = User::create([
            'username' => 'admin_test',
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('Admin@1234'),
            'role' => 'admin',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager_test',
            'email' => 'manager@test.com',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => 'manager',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser1 = User::create([
            'username' => 'teller1_test',
            'email' => 'teller1@test.com',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser2 = User::create([
            'username' => 'teller2_test',
            'email' => 'teller2@test.com',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

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

        // Create currency (use firstOrCreate to avoid conflicts with seeders or other tests)
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

        // Open till for transactions
        TillBalance::create([
            'till_id' => 'MAIN',
            'currency_code' => 'USD',
            'date' => today(),
            'opening_balance' => '10000',
            'current_balance' => '10000',
            'transaction_total' => '0',
            'foreign_total' => '0',
            'opened_by' => $this->tellerUser1->id,
        ]);

        // Create initial position
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '10000',
            'avg_cost_rate' => '4.5000',
        ]);

        // Create chart of accounts entries needed for journal entries
        \App\Models\ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            [
                'account_name' => 'Cash - MYR',
                'account_type' => 'Asset',
                'is_active' => true,
            ]
        );

        \App\Models\ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            [
                'account_name' => 'Foreign Currency Inventory',
                'account_type' => 'Asset',
                'is_active' => true,
            ]
        );

        // Create currencies needed for testing (both Buy and Sell need other currencies)
        \App\Models\Currency::firstOrCreate(
            ['code' => 'EUR'],
            [
                'name' => 'Euro',
                'symbol' => '€',
                'rate_buy' => 5.1000,
                'rate_sell' => 5.1500,
                'is_active' => true,
            ]
        );
    }

    /**
     * Create a completed transaction for testing
     */
    protected function createCompletedTransaction(User $user, array $overrides = []): Transaction
    {
        $data = array_merge([
            'customer_id' => $this->customer->id,
            'user_id' => $user->id,
            'till_id' => 'MAIN',
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1000',
            'amount_local' => '4500',
            'rate' => '4.5000',
            'purpose' => 'Travel expenses',
            'source_of_funds' => 'Savings',
            'status' => 'Completed',
            'cdd_level' => 'Simplified',
            'created_at' => now(),
        ], $overrides);

        // Handle created_at separately to ensure it's set properly
        $transaction = new Transaction($data);
        if (isset($overrides['created_at'])) {
            $transaction->created_at = $overrides['created_at'];
        }
        $transaction->save();

        return $transaction;
    }

    /**
     * Test that a transaction can be cancelled within 24 hours by manager
     */
    public function test_transaction_can_be_cancelled_within_24_hours_by_manager(): void
    {
        // Create completed transaction
        $transaction = $this->createCompletedTransaction($this->tellerUser1);

        // Login as manager
        $this->actingAs($this->managerUser);

        // Get the cancel form
        $response = $this->get(route('transactions.cancel.show', $transaction));
        $response->assertStatus(200);
        $response->assertSee('Cancel Transaction');

        // Post to cancel
        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Customer requested cancellation due to change of plans',
            'confirm_understanding' => '1',
        ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('success', 'Transaction cancelled successfully. Refund transaction created.');

        // Assert transaction status is Cancelled
        $transaction->refresh();
        $this->assertEquals('Cancelled', $transaction->status);
        $this->assertNotNull($transaction->cancelled_at);
        $this->assertEquals($this->managerUser->id, $transaction->cancelled_by);
        $this->assertEquals('Customer requested cancellation due to change of plans', $transaction->cancellation_reason);

        // Assert refund transaction created
        $refundTransaction = Transaction::where('original_transaction_id', $transaction->id)->first();
        $this->assertNotNull($refundTransaction);
        $this->assertTrue((bool) $refundTransaction->is_refund);
        $this->assertEquals('Sell', $refundTransaction->type); // Reverse of Buy
        $this->assertEquals($transaction->amount_foreign, $refundTransaction->amount_foreign);
        $this->assertEquals($transaction->amount_local, $refundTransaction->amount_local);

        // Assert system log created
        $log = SystemLog::where('action', 'transaction_cancelled')
            ->where('entity_id', $transaction->id)
            ->first();
        $this->assertNotNull($log);
    }

    /**
     * Test that a transaction can be cancelled by admin
     */
    public function test_transaction_can_be_cancelled_by_admin(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Admin cancellation for testing',
            'confirm_understanding' => '1',
        ]);

        $response->assertRedirect();
        $transaction->refresh();
        $this->assertEquals('Cancelled', $transaction->status);
    }

    /**
     * Test that original teller can cancel their own transaction
     */
    public function test_original_teller_can_cancel_own_transaction(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1);

        $this->actingAs($this->tellerUser1);

        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Made an error in the transaction',
            'confirm_understanding' => '1',
        ]);

        $response->assertRedirect();
        $transaction->refresh();
        $this->assertEquals('Cancelled', $transaction->status);
        $this->assertEquals($this->tellerUser1->id, $transaction->cancelled_by);
    }

    /**
     * Test that another teller cannot cancel someone else's transaction
     */
    public function test_other_teller_cannot_cancel_transaction(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1);

        $this->actingAs($this->tellerUser2);

        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Trying to cancel someone else transaction',
            'confirm_understanding' => '1',
        ]);

        $response->assertStatus(403);
        $transaction->refresh();
        $this->assertEquals('Completed', $transaction->status);
    }

    /**
     * Test that transaction cannot be cancelled after 24 hours
     */
    public function test_transaction_cannot_be_cancelled_after_24_hours(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1, [
            'created_at' => now()->subHours(25),
        ]);

        $this->actingAs($this->managerUser);

        // Try to access cancel form
        $response = $this->get(route('transactions.cancel.show', $transaction));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'This transaction cannot be cancelled.');

        // Try to post cancel
        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Trying to cancel old transaction',
            'confirm_understanding' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This transaction cannot be cancelled.');

        $transaction->refresh();
        $this->assertEquals('Completed', $transaction->status);
    }

    /**
     * Test that only completed transactions can be cancelled
     */
    public function test_only_completed_transactions_can_be_cancelled(): void
    {
        // Create pending transaction
        $pendingTransaction = $this->createCompletedTransaction($this->tellerUser1, [
            'status' => 'Pending',
        ]);
        $pendingTransaction->created_at = now()->subHour();
        $pendingTransaction->save();

        $this->actingAs($this->managerUser);

        $response = $this->get(route('transactions.cancel.show', $pendingTransaction));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'This transaction cannot be cancelled.');
    }

    /**
     * Test that refund transactions cannot be cancelled
     */
    public function test_refund_transactions_cannot_be_cancelled(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1, [
            'is_refund' => true,
        ]);

        $this->actingAs($this->managerUser);

        $response = $this->get(route('transactions.cancel.show', $transaction));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'This transaction cannot be cancelled.');
    }

    /**
     * Test that already cancelled transactions cannot be cancelled again
     */
    public function test_already_cancelled_transactions_cannot_be_cancelled_again(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1, [
            'cancelled_at' => now(),
            'cancelled_by' => $this->managerUser->id,
            'cancellation_reason' => 'Already cancelled',
        ]);

        $this->actingAs($this->managerUser);

        $response = $this->get(route('transactions.cancel.show', $transaction));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'This transaction cannot be cancelled.');
    }

    /**
     * Test validation of cancellation reason
     */
    public function test_cancellation_reason_is_required_and_min_length(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1);

        $this->actingAs($this->managerUser);

        // Test empty reason
        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => '',
            'confirm_understanding' => '1',
        ]);
        $response->assertSessionHasErrors('cancellation_reason');

        // Test short reason
        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Short',
            'confirm_understanding' => '1',
        ]);
        $response->assertSessionHasErrors('cancellation_reason');

        // Test reason too long
        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => str_repeat('a', 1001),
            'confirm_understanding' => '1',
        ]);
        $response->assertSessionHasErrors('cancellation_reason');
    }

    /**
     * Test that confirmation checkbox is required
     */
    public function test_confirmation_checkbox_is_required(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1);

        $this->actingAs($this->managerUser);

        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Valid reason for cancellation',
            'confirm_understanding' => '',
        ]);

        $response->assertSessionHasErrors('confirm_understanding');
    }

    /**
     * Test that cancel button appears for refundable transactions
     */
    public function test_cancel_button_appears_for_refundable_transactions(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1);

        $this->actingAs($this->managerUser);

        $response = $this->get(route('transactions.show', $transaction));
        $response->assertStatus(200);
        $response->assertSee('Cancel Transaction');
        $response->assertSee(route('transactions.cancel.show', $transaction));
    }

    /**
     * Test that cancel button does not appear for non-refundable transactions
     */
    public function test_cancel_button_does_not_appear_for_old_transactions(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1, [
            'created_at' => now()->subHours(25),
        ]);

        $this->actingAs($this->managerUser);

        $response = $this->get(route('transactions.show', $transaction));
        $response->assertStatus(200);
        $response->assertDontSee('Cancel Transaction');
    }

    /**
     * Test stock position is reversed after cancellation
     */
    public function test_stock_position_is_reversed_after_cancellation(): void
    {
        // Create initial position
        $initialBalance = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'MAIN')
            ->first();
        $initialAmount = $initialBalance->balance;

        // Create and complete a buy transaction
        $transaction = $this->createCompletedTransaction($this->tellerUser1, [
            'type' => 'Buy',
            'amount_foreign' => '500',
        ]);

        // Update position as if transaction was processed
        app(\App\Services\CurrencyPositionService::class)->updatePosition(
            'USD',
            '500',
            '4.5000',
            'Buy',
            'MAIN'
        );

        $positionAfterBuy = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'MAIN')
            ->first();

        $this->actingAs($this->managerUser);

        // Cancel the transaction
        $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Reversing transaction for testing',
            'confirm_understanding' => '1',
        ]);

        // Position should be back to initial
        $positionAfterCancel = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'MAIN')
            ->first();

        // The balance should have been reversed (Sell 500)
        $this->assertEquals($initialAmount, $positionAfterCancel->balance);
    }

    /**
     * Test that cancellation creates a refund transaction with reversed type
     */
    public function test_refund_transaction_has_reversed_type(): void
    {
        // Test Buy -> Sell reversal
        $buyTransaction = $this->createCompletedTransaction($this->tellerUser1, [
            'type' => 'Buy',
        ]);

        $this->actingAs($this->managerUser);

        $this->post(route('transactions.cancel', $buyTransaction), [
            'cancellation_reason' => 'Testing refund type reversal',
            'confirm_understanding' => '1',
        ]);

        $refund = Transaction::where('original_transaction_id', $buyTransaction->id)->first();
        $this->assertEquals('Sell', $refund->type);

        // Test Sell -> Buy reversal
        $sellTransaction = $this->createCompletedTransaction($this->tellerUser1, [
            'type' => 'Sell',
        ]);

        $this->post(route('transactions.cancel', $sellTransaction), [
            'cancellation_reason' => 'Testing refund type reversal for sell',
            'confirm_understanding' => '1',
        ]);

        $refund = Transaction::where('original_transaction_id', $sellTransaction->id)->first();
        $this->assertEquals('Buy', $refund->type);
    }

    /**
     * Test that guest users cannot access cancellation
     */
    public function test_guest_users_cannot_access_cancellation(): void
    {
        $transaction = $this->createCompletedTransaction($this->tellerUser1);

        // Try to access cancel form as guest
        $response = $this->get(route('transactions.cancel.show', $transaction));
        $response->assertRedirect('/login');

        // Try to post cancel as guest
        $response = $this->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Guest trying to cancel',
            'confirm_understanding' => '1',
        ]);
        $response->assertRedirect('/login');
    }
}
