<?php

namespace Tests\Feature\EdgeCase;

use App\Enums\CddLevel;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Boundary Value Analysis Tests
 *
 * Tests system behavior at threshold boundaries to ensure correct handling
 * of edge cases around monetary thresholds, CDD levels, and approval requirements.
 *
 * CDD Thresholds:
 * - Simplified: < RM 3,000
 * - Standard: RM 3,000 - RM 49,999.99
 * - Enhanced: ≥ RM 50,000
 *
 * Approval Threshold: RM 50,000
 * CTOS Reporting Threshold: RM 10,000 (cash transactions)
 */
class BoundaryValueTest extends TestCase
{
    use RefreshDatabase;

    protected User $tellerUser;

    protected User $managerUser;

    protected Customer $customer;

    protected Currency $currency;

    protected TillBalance $tillBalance;

    /**
     * Helper to create authenticated test session with MFA verified
     */
    protected function actingAsMfaVerified(User $user): self
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->tillBalance = TillBalance::create([
            'till_id' => 'MAIN',
            'currency_code' => 'USD',
            'opening_balance' => '10000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);

        AccountingPeriod::create([
            'period_code' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        // Create required chart of accounts
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

        // Create currency position with stock
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '5000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);
    }

    // =============================================================================
    // CDD Threshold Boundary Tests
    // =============================================================================

    /**
     * Test transaction at RM 2,999.99 uses Simplified CDD
     * Just below the Standard threshold
     */
    public function test_transaction_at_2999_99_uses_simplified_cdd(): void
    {
        // RM 2,999.99 at rate 4.72 = ~635.59 USD
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '635.59',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(CddLevel::Simplified->value, $transaction->cdd_level->value);
        $this->assertEquals(TransactionStatus::Completed->value, $transaction->status->value);
    }

    /**
     * Test transaction at exactly RM 3,000.00 uses Standard CDD
     * Right at the Standard threshold boundary
     */
    public function test_transaction_at_3000_00_uses_standard_cdd(): void
    {
        // Use rate 1.0 and amount 3000 to get exactly RM 3,000 local amount
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '3000.00',
            'rate' => '1.0000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)
            ->where('amount_local', '3000.0000')
            ->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(CddLevel::Standard->value, $transaction->cdd_level->value);
    }

    /**
     * Test transaction at RM 3,000.01 uses Standard CDD
     * Just above the Standard threshold
     */
    public function test_transaction_at_3000_01_uses_standard_cdd(): void
    {
        // RM 3,000.01 at rate 4.72 = ~635.60 USD
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '635.60',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(CddLevel::Standard->value, $transaction->cdd_level->value);
    }

    /**
     * Test transaction at RM 49,999.99 uses Standard CDD
     * Just below the Enhanced threshold
     */
    public function test_transaction_at_49999_99_uses_standard_cdd(): void
    {
        // RM 49,999.99 at rate 4.72 = ~10,593.22 USD
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '10593.22',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(CddLevel::Standard->value, $transaction->cdd_level->value);
    }

    /**
     * Test transaction at exactly RM 50,000.00 uses Enhanced CDD and requires approval
     * Right at the Enhanced threshold boundary
     */
    public function test_transaction_at_50000_00_uses_enhanced_cdd_and_requires_approval(): void
    {
        // Use rate 1.0 and amount 50000 to get exactly RM 50,000 local amount
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '50000.00',
            'rate' => '1.0000',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();
        // Controller returns 'warning' flash message for pending transactions
        $response->assertSessionHas('warning');

        $transaction = Transaction::where('customer_id', $this->customer->id)
            ->where('amount_local', '50000.0000')
            ->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(CddLevel::Enhanced->value, $transaction->cdd_level->value);
        $this->assertEquals(TransactionStatus::Pending->value, $transaction->status->value);

        // Manager must approve
        $managerResponse = $this->actingAsMfaVerified($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");
        $managerResponse->assertRedirect();

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::Completed->value, $transaction->status->value);
    }

    /**
     * Test transaction at RM 50,000.01 uses Enhanced CDD
     * Just above the Enhanced threshold
     */
    public function test_transaction_at_50000_01_uses_enhanced_cdd(): void
    {
        // RM 50,000.01 at rate 4.72 = ~10,593.22 USD (rounded)
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '10593.23',
            'rate' => '4.7200',
            'purpose' => 'Investment',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $transaction = Transaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(CddLevel::Enhanced->value, $transaction->cdd_level->value);
        $this->assertEquals(TransactionStatus::Pending->value, $transaction->status->value);
    }

    // =============================================================================
    // Amount Precision Tests
    // =============================================================================

    /**
     * Test transaction with maximum precision amount
     * 4 decimal places should be handled correctly
     */
    public function test_transaction_with_maximum_precision_amount(): void
    {
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '999.9999',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('999.9999', $transaction->amount_foreign);
    }

    /**
     * Test transaction with very small amount
     * Edge case: minimal transaction amount (0.01 is the minimum valid amount)
     */
    public function test_transaction_with_very_small_amount(): void
    {
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '0.01',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)
            ->where('amount_foreign', '0.0100')
            ->first();
        $this->assertNotNull($transaction);
        // Database stores as decimal(18,4), so 0.01 becomes 0.0100
        $this->assertEquals('0.0100', $transaction->amount_foreign);
    }

    /**
     * Test transaction with zero amount should fail
     */
    public function test_transaction_with_zero_amount_fails(): void
    {
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '0',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertSessionHasErrors('amount_foreign');

        $this->assertDatabaseMissing('transactions', [
            'customer_id' => $this->customer->id,
            'amount_foreign' => '0',
        ]);
    }

    /**
     * Test transaction with negative amount should fail
     */
    public function test_transaction_with_negative_amount_fails(): void
    {
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '-100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertSessionHasErrors('amount_foreign');

        $this->assertDatabaseMissing('transactions', [
            'customer_id' => $this->customer->id,
            'amount_foreign' => '-100',
        ]);
    }

    // =============================================================================
    // Exchange Rate Boundary Tests
    // =============================================================================

    /**
     * Test transaction with very small rate
     */
    public function test_transaction_with_minimum_rate(): void
    {
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '0.0001',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)
            ->where('rate', '0.000100')
            ->first();
        $this->assertNotNull($transaction);
        // Database stores rate as decimal(18,6), so 0.0001 becomes 0.000100
        $this->assertEquals('0.000100', $transaction->rate);
    }

    /**
     * Test transaction with very large rate
     */
    public function test_transaction_with_large_rate(): void
    {
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '1',
            'rate' => '999.9999',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)
            ->where('rate', '999.999900')
            ->first();
        $this->assertNotNull($transaction);
        // Database stores rate as decimal(18,6), so 999.9999 becomes 999.999900
        $this->assertEquals('999.999900', $transaction->rate);
    }

    // =============================================================================
    // Currency Position Boundary Tests
    // =============================================================================

    /**
     * Test sell transaction with exact available stock
     */
    public function test_sell_with_exact_available_stock_succeeds(): void
    {
        // Currency position has exactly 5000 USD
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '5000.00',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $position = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'MAIN')
            ->first();
        $this->assertNotNull($position);
        $this->assertEquals('0.0000', $position->balance);
    }

    /**
     * Test sell transaction with amount just above available stock fails
     */
    public function test_sell_with_insufficient_stock_fails(): void
    {
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '5000.01',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('transactions', [
            'customer_id' => $this->customer->id,
            'type' => TransactionType::Sell->value,
        ]);
    }

    // =============================================================================
    // Risk Rating Boundary Tests
    // =============================================================================

    /**
     * Test high risk customer always requires Enhanced CDD regardless of amount
     */
    public function test_high_risk_customer_requires_enhanced_cdd_regardless_of_amount(): void
    {
        $highRiskCustomer = Customer::create([
            'full_name' => 'High Risk Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789013'),
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('123 Test Street'),
            'contact_number_encrypted' => encrypt('0123456788'),
            'email' => 'highrisk@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'High',
        ]);

        // Small transaction with high risk customer
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $highRiskCustomer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $highRiskCustomer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(CddLevel::Enhanced->value, $transaction->cdd_level->value);
    }

    /**
     * Test PEP status always triggers Enhanced CDD
     */
    public function test_pep_customer_requires_enhanced_cdd_regardless_of_amount(): void
    {
        $pepCustomer = Customer::create([
            'full_name' => 'PEP Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789014'),
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('123 Test Street'),
            'contact_number_encrypted' => encrypt('0123456787'),
            'email' => 'pep@test.com',
            'pep_status' => true,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'Low',
        ]);

        // Small transaction with PEP customer
        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $pepCustomer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $pepCustomer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(CddLevel::Enhanced->value, $transaction->cdd_level->value);
    }

    // =============================================================================
    // String Length Boundary Tests
    // =============================================================================

    /**
     * Test transaction with maximum length purpose field
     */
    public function test_transaction_with_maximum_length_purpose(): void
    {
        $longPurpose = str_repeat('A', 255);

        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => $longPurpose,
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($transaction);
    }

    /**
     * Test transaction with purpose exceeding max length fails gracefully
     */
    public function test_transaction_with_excessive_purpose_length_fails(): void
    {
        $tooLongPurpose = str_repeat('A', 500);

        $response = $this->actingAsMfaVerified($this->tellerUser)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => $tooLongPurpose,
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        // Should either fail validation or truncate
        $response->assertRedirect();

        $transaction = Transaction::where('customer_id', $this->customer->id)->first();
        if ($transaction) {
            $this->assertLessThanOrEqual(255, strlen($transaction->purpose));
        }
    }

    // =============================================================================
    // Integer Boundary Tests
    // =============================================================================

    /**
     * Test transaction with large integer transaction ID
     * Database should handle large integer values (using bigint unsigned)
     * Note: Direct ID insertion with auto-increment requires raw SQL
     */
    public function test_database_handles_large_transaction_ids(): void
    {
        // Use a large but realistic ID (not PHP_INT_MAX which would exceed bigint limit)
        // MySQL bigint signed max is 9223372036854775807 (same as PHP_INT_MAX on 64-bit)
        // Use a very large ID that's still within practical limits
        $largeId = 9999999999999; // 13 digits - well within bigint range

        // Insert directly via DB facade to bypass auto-increment and model events
        \Illuminate\Support\Facades\DB::table('transactions')->insert([
            'id' => $largeId,
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy->value,
            'currency_code' => 'USD',
            'amount_foreign' => '100.0000',
            'amount_local' => '472.0000',
            'rate' => '4.720000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => TransactionStatus::Completed->value,
            'cdd_level' => CddLevel::Simplified->value,
            'version' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Retrieve and verify
        $largeIdTransaction = Transaction::find($largeId);
        $this->assertNotNull($largeIdTransaction);
        $this->assertEquals($largeId, $largeIdTransaction->id);

        // Cleanup
        Transaction::destroy($largeId);
    }
}
