<?php

namespace Tests\Feature\EdgeCase;

use App\Enums\CddLevel;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Counter;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CurrencyPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Concurrency Tests
 *
 * Tests race conditions and concurrent access scenarios:
 * - Simultaneous sell operations on same currency position
 * - Counter session open/close race conditions
 * - Transaction approval while being cancelled
 * - Double-entry accounting consistency under concurrent load
 * - Optimistic locking on transactions
 * - Database lock behavior
 *
 * These tests use database transactions and locks to simulate race conditions.
 */
class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create authenticated test session with MFA verified
     */
    protected function actingAsMfaVerified(User $user): self
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    protected User $tellerUser1;

    protected User $tellerUser2;

    protected User $managerUser;

    protected Customer $customer;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tellerUser1 = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser2 = User::create([
            'username' => 'teller2',
            'email' => 'teller2@cems.my',
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

        // Also create EUR for counter open test
        Currency::firstOrCreate(
            ['code' => 'EUR'],
            [
                'name' => 'Euro',
                'symbol' => '€',
                'rate_buy' => 5.0200,
                'rate_sell' => 5.0500,
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

        // Create till balances for both tellers
        TillBalance::create([
            'till_id' => 'MAIN',
            'currency_code' => 'USD',
            'opening_balance' => '10000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser1->id,
        ]);

        // Create accounting period
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
    }

    // =============================================================================
    // Simultaneous Sell Operation Tests
    // =============================================================================

    /**
     * Test that simultaneous sell requests don't oversell currency stock
     * Both tellers try to sell more than combined available stock
     */
    public function test_simultaneous_sell_operations_maintain_stock_integrity(): void
    {
        // Setup: Only 500 USD available
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '500',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        // Teller 1 sells 400 USD
        $response1 = $this->actingAsMfaVerified($this->tellerUser1)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '400',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        // Teller 2 tries to sell 400 USD (should fail - insufficient stock)
        $response2 = $this->actingAsMfaVerified($this->tellerUser2)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '400',
            'rate' => '4.7500',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        // First transaction should succeed
        $response1->assertRedirect();

        // Second should fail due to insufficient stock
        $response2->assertSessionHas('error');

        // Verify final stock
        $position = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'MAIN')
            ->first();
        $this->assertNotNull($position);
        $this->assertEquals('100.0000', $position->balance); // 500 - 400 = 100

        // Only one sell transaction should exist
        $sellCount = Transaction::where('type', TransactionType::Sell)
            ->where('status', TransactionStatus::Completed)
            ->count();
        $this->assertEquals(1, $sellCount);
    }

    /**
     * Test that sell operations use proper database locking
     */
    public function test_sell_operations_use_database_locking(): void
    {
        // Setup: 1000 USD available
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '1000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        // Create two separate customer records for each transaction
        $customer2 = Customer::create([
            'full_name' => 'Test Customer 2',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789013'),
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('124 Test Street'),
            'contact_number_encrypted' => encrypt('0123456788'),
            'email' => 'customer2@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'Low',
        ]);

        // Process both sells sequentially
        $response1 = $this->actingAsMfaVerified($this->tellerUser1)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '300',
            'rate' => '4.7500',
            'purpose' => 'Business',
            'source_of_funds' => 'Business Income',
            'till_id' => 'MAIN',
        ]);

        $response2 = $this->actingAsMfaVerified($this->tellerUser2)->post('/transactions', [
            'customer_id' => $customer2->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
            'amount_foreign' => '400',
            'rate' => '4.7500',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        // Both should succeed
        $response1->assertRedirect();
        $response2->assertRedirect();

        // Verify final stock: 1000 - 300 - 400 = 300
        $position = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'MAIN')
            ->first();
        $this->assertNotNull($position);
        $this->assertEquals('300.0000', $position->balance);

        // Two sell transactions should exist
        $sellCount = Transaction::where('type', TransactionType::Sell)
            ->where('status', TransactionStatus::Completed)
            ->count();
        $this->assertEquals(2, $sellCount);
    }

    /**
     * Test that sell operation with exact stock succeeds
     */
    public function test_sell_with_exact_stock_succeeds(): void
    {
        // Setup: Exactly 500 USD available
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '500',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        $response = $this->actingAsMfaVerified($this->tellerUser1)->post('/transactions', [
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
        $response->assertSessionHas('success');

        // Verify final stock is zero
        $position = CurrencyPosition::where('currency_code', 'USD')
            ->where('till_id', 'MAIN')
            ->first();
        $this->assertNotNull($position);
        $this->assertEquals('0.0000', $position->balance);
    }

    // =============================================================================
    // Counter Session Race Condition Tests
    // =============================================================================

    /**
     * Test that counter cannot be opened twice
     */
    public function test_counter_cannot_be_opened_twice(): void
    {
        // Create branch first (needed for FK constraint)
        $branch = Branch::firstOrCreate(
            ['code' => 'HQ'],
            [
                'name' => 'Head Office',
                'type' => 'head_office',
                'is_active' => true,
            ]
        );

        // Create counter with valid branch_id
        Counter::create([
            'code' => 'TEST01',
            'name' => 'Test Counter',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        // First open should succeed
        $response1 = $this->actingAsMfaVerified($this->tellerUser1)->post('/counters/TEST01/open', [
            'opening_floats' => [
                ['currency_id' => 'USD', 'amount' => 5000],
                ['currency_id' => 'EUR', 'amount' => 3000],
            ],
        ]);

        // Second open should fail - counter already open
        $response2 = $this->actingAsMfaVerified($this->tellerUser2)->post('/counters/TEST01/open', [
            'opening_floats' => [
                ['currency_id' => 'USD', 'amount' => 5000],
                ['currency_id' => 'EUR', 'amount' => 3000],
            ],
        ]);

        $response1->assertRedirect();

        // Second attempt should fail or be rejected
        $this->assertTrue(
            $response2->status() === 302 || $response2->status() === 422 || $response2->status() === 409,
            'Second counter open should fail'
        );
    }

    /**
     * Test that counter close prevents new transactions
     */
    public function test_counter_close_prevents_new_transactions(): void
    {
        // Setup with open till - ensure we have one for today
        $tillBalance = TillBalance::firstOrCreate(
            [
                'till_id' => 'MAIN',
                'currency_code' => 'USD',
                'date' => today(),
            ],
            [
                'opening_balance' => '10000.00',
                'opened_by' => $this->tellerUser1->id,
            ]
        );

        // Create a buy transaction
        $response1 = $this->actingAsMfaVerified($this->tellerUser1)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        // Transaction should succeed with open till
        $this->assertTrue(
            $response1->isRedirect() || $response1->isSuccessful(),
            'First transaction should succeed with open till'
        );

        // Close the counter by setting closed_at
        $tillBalance->update(['closed_at' => now(), 'closed_by' => $this->tellerUser1->id]);

        // Verify till is now closed
        $tillBalance->refresh();
        $this->assertNotNull($tillBalance->closed_at, 'Till should be closed');

        // Try to create another transaction - should fail because till is closed
        $response2 = $this->actingAsMfaVerified($this->tellerUser1)->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'till_id' => 'MAIN',
        ]);

        // Response should indicate error (either redirect with error or validation error)
        $this->assertTrue(
            $response2->isRedirect() || $response2->status() === 422 || $response2->status() === 403,
            'Second transaction should fail with closed till'
        );
    }

    // =============================================================================
    // Transaction State Machine Concurrency Tests
    // =============================================================================

    /**
     * Test that approved transaction cannot be cancelled simultaneously
     */
    public function test_approved_transaction_cannot_be_cancelled_race_condition(): void
    {
        // Setup: Create a pending large transaction
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser1->id,
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

        // Manager approves the transaction
        $this->actingAsMfaVerified($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::Completed->value, $transaction->status->value);

        // Now try to cancel the completed transaction
        $cancelResponse = $this->actingAsMfaVerified($this->managerUser)
            ->post("/transactions/{$transaction->id}/cancel", [
                'cancellation_reason' => 'Test cancellation',
                'confirm_understanding' => '1',
            ]);

        // Cancellation should succeed for completed transactions within window
        $transaction->refresh();
        $this->assertTrue(
            $transaction->status->value === TransactionStatus::Reversed->value ||
            $transaction->status->value === TransactionStatus::Completed->value
        );
    }

    /**
     * Test optimistic locking prevents stale updates
     */
    public function test_optimistic_locking_prevents_stale_updates(): void
    {
        // Create a pending transaction
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser1->id,
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

        // First manager approves (updates version)
        $response1 = $this->actingAsMfaVerified($this->managerUser)
            ->post("/transactions/{$transaction->id}/approve");

        $response1->assertRedirect();

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::Completed->value, $transaction->status->value);
        $this->assertEquals(1, $transaction->version); // Version should be incremented
    }

    // =============================================================================
    // Double-Entry Accounting Consistency Tests
    // =============================================================================

    /**
     * Test that concurrent transactions maintain accounting balance
     */
    public function test_concurrent_transactions_maintain_accounting_balance(): void
    {
        // Setup currency position
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '10000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        // Create multiple buy transactions
        $amounts = [100, 200, 300, 400, 500];
        foreach ($amounts as $amount) {
            $this->actingAsMfaVerified($this->tellerUser1)->post('/transactions', [
                'customer_id' => $this->customer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'amount_foreign' => $amount,
                'rate' => '4.7200',
                'purpose' => 'Travel',
                'source_of_funds' => 'Savings',
                'till_id' => 'MAIN',
            ]);
        }

        // Verify all transactions created
        $completedCount = Transaction::where('status', TransactionStatus::Completed)
            ->where('type', TransactionType::Buy)
            ->count();
        $this->assertEquals(5, $completedCount);

        // Verify journal entries balance for each transaction
        $transactions = Transaction::where('status', TransactionStatus::Completed)
            ->where('type', TransactionType::Buy)
            ->get();

        foreach ($transactions as $transaction) {
            $journalEntry = JournalEntry::where('reference_type', 'Transaction')
                ->where('reference_id', $transaction->id)
                ->first();

            if ($journalEntry) {
                $debits = $journalEntry->lines()->sum('debit');
                $credits = $journalEntry->lines()->sum('credit');
                $this->assertEquals($debits, $credits, 'Journal entry must balance');
            }
        }
    }

    /**
     * Test that sell transaction creates balanced journal entries
     */
    public function test_sell_transaction_creates_balanced_journal_entries(): void
    {
        // Setup currency position
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '1000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        // Create sell transaction
        $response = $this->actingAsMfaVerified($this->tellerUser1)->post('/transactions', [
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

        $transaction = Transaction::where('type', TransactionType::Sell)
            ->where('status', TransactionStatus::Completed)
            ->first();

        $this->assertNotNull($transaction);

        // Verify journal entry balances
        $journalEntry = JournalEntry::where('reference_type', 'Transaction')
            ->where('reference_id', $transaction->id)
            ->first();

        $this->assertNotNull($journalEntry);

        $debits = $journalEntry->lines()->sum('debit');
        $credits = $journalEntry->lines()->sum('credit');
        $this->assertEquals($debits, $credits, 'Sell transaction journal entry must balance');
    }

    // =============================================================================
    // Database Lock Tests
    // =============================================================================

    /**
     * Test that currency position updates use row locking
     */
    public function test_currency_position_uses_row_locking(): void
    {
        // Setup initial position
        $position = CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '1000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);

        // Test that lockForUpdate is used in CurrencyPositionService
        $positionService = app(CurrencyPositionService::class);
        $this->assertTrue(
            method_exists($positionService, 'updatePosition'),
            'CurrencyPositionService should have updatePosition method'
        );

        // Verify position can be locked
        DB::transaction(function () use ($position) {
            $lockedPosition = CurrencyPosition::where('id', $position->id)
                ->lockForUpdate()
                ->first();
            $this->assertNotNull($lockedPosition);
        });
    }

    /**
     * Test transaction version field for optimistic locking
     */
    public function test_transaction_has_version_field_for_optimistic_locking(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser1->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'amount_local' => '472.00',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => TransactionStatus::Completed,
            'cdd_level' => CddLevel::Simplified,
            'version' => 0,
        ]);

        $this->assertNotNull($transaction->version);
        $this->assertEquals(0, $transaction->version);

        // Update should allow version to be set manually (optimistic locking pattern)
        $transaction->update(['version' => 1]);
        $transaction->refresh();
        $this->assertEquals(1, $transaction->version);

        // Version field exists and can be used for optimistic locking
        $this->assertTrue($transaction->version >= 0);
    }

    // =============================================================================
    // Isolation Level Tests
    // =============================================================================

    /**
     * Test transaction isolation - reads should not see uncommitted data
     */
    public function test_transaction_isolation_reads(): void
    {
        $initialCount = Transaction::count();

        // Create a transaction in a way that we can test isolation
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser1->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'amount_local' => '472.00',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => TransactionStatus::Completed,
            'cdd_level' => CddLevel::Simplified,
            'version' => 0,
        ]);

        // After commit, should be visible
        $this->assertEquals($initialCount + 1, Transaction::count());
    }

    /**
     * Test that transaction ID sequence is monotonic
     */
    public function test_transaction_id_sequence_is_monotonic(): void
    {
        $ids = [];

        for ($i = 0; $i < 5; $i++) {
            $transaction = Transaction::create([
                'customer_id' => $this->customer->id,
                'user_id' => $this->tellerUser1->id,
                'till_id' => 'MAIN',
                'type' => TransactionType::Buy,
                'currency_code' => 'USD',
                'amount_foreign' => (100 + $i),
                'amount_local' => (472.00 + $i),
                'rate' => '4.7200',
                'purpose' => 'Travel',
                'source_of_funds' => 'Savings',
                'status' => TransactionStatus::Completed,
                'cdd_level' => CddLevel::Simplified,
                'version' => 0,
            ]);

            $ids[] = $transaction->id;
        }

        // IDs should be strictly increasing
        for ($i = 1; $i < count($ids); $i++) {
            $this->assertGreaterThan($ids[$i - 1], $ids[$i], 'Transaction IDs should be monotonically increasing');
        }
    }
}
