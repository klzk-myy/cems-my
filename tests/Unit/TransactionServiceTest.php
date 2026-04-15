<?php

namespace Tests\Unit;

use App\Enums\CddLevel;
use App\Enums\TellerAllocationStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\TellerAllocation;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $transactionService;

    protected User $teller;

    protected User $manager;

    protected Branch $branch;

    protected Counter $counter;

    protected Currency $currency;

    protected Customer $customer;

    protected TillBalance $tillBalance;

    protected function setUp(): void
    {
        parent::setUp();

        // Use Laravel container to resolve services with correct dependencies
        $this->transactionService = app(TransactionService::class);

        // Setup test data
        $this->setupTestData();
    }

    protected function setupTestData(): void
    {
        // Use seeded currency instead of creating
        $this->currency = Currency::where('code', 'USD')->firstOrFail();

        $this->branch = Branch::create([
            'code' => 'HQ-TEST',
            'name' => 'Test Head Office',
            'address' => '123 Test Street',
            'phone' => '+60312345678',
            'email' => 'test@localhost.com',
            'is_active' => true,
        ]);

        $this->counter = Counter::create([
            'name' => 'Test Counter',
            'code' => 'CTR-TEST',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'full_name' => 'Test Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789012'),
            'nationality' => 'MY',
            'date_of_birth' => '1990-01-15',
            'risk_rating' => 'Low',
            'cdd_level' => 'Simplified',
            'is_active' => true,
        ]);

        $this->teller = User::create([
            'username' => 'testteller',
            'email' => 'teller@test.com',
            'password_hash' => bcrypt('password'),
            'role' => UserRole::Teller,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'username' => 'testmanager',
            'email' => 'manager@test.com',
            'password_hash' => bcrypt('password'),
            'role' => UserRole::Manager,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        // Create till balance (open till)
        $this->tillBalance = TillBalance::create([
            'till_id' => $this->counter->id,
            'branch_id' => $this->branch->id,
            'currency_code' => $this->currency->code,
            'date' => today(),
            'opening_balance' => '10000.00',
            'transaction_total' => '0',
            'foreign_total' => '0',
            'opened_by' => $this->teller->id,
        ]);

        // Create active teller allocation for the currency (required by TransactionService)
        TellerAllocation::create([
            'user_id' => $this->teller->id,
            'branch_id' => $this->branch->id,
            'counter_id' => $this->counter->id,
            'currency_code' => $this->currency->code,
            'allocated_amount' => '60000.0000',
            'current_balance' => '60000.0000',
            'requested_amount' => '60000.0000',
            'daily_limit_myr' => '500000.0000',
            'daily_used_myr' => '0.0000',
            'status' => TellerAllocationStatus::ACTIVE,
            'session_date' => today(),
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
            'opened_at' => now(),
        ]);
    }

    public function test_can_create_buy_transaction(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '100.00',
            'rate' => '4.500000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
            'idempotency_key' => uniqid('test_', true),
        ];

        $transaction = $this->transactionService->createTransaction($data, $this->teller->id);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(TransactionStatus::Completed, $transaction->status);
        $this->assertEquals(TransactionType::Buy, $transaction->type);
        // Amount is stored as-provided (string), calculated amount uses BCMath
        // Check that amount_local is approximately 450 (with 6 decimal precision)
        $this->assertEqualsWithDelta(450.0, (float) $transaction->amount_local, 0.01);
        $this->assertEquals(CddLevel::Simplified, $transaction->cdd_level);
    }

    public function test_buy_transaction_with_insufficient_stock_position_is_created(): void
    {
        // For buy transactions, position doesn't need to exist beforehand
        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '500.00',
            'rate' => '4.500000',
            'purpose' => 'Investment',
            'source_of_funds' => 'Savings',
            'idempotency_key' => uniqid('test_', true),
        ];

        // Should succeed even without existing position
        $transaction = $this->transactionService->createTransaction($data, $this->teller->id);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(TransactionStatus::Completed, $transaction->status);
    }

    public function test_large_transaction_requires_hold(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '12000.00',
            'rate' => '4.500000',
            'purpose' => 'Property Purchase',
            'source_of_funds' => 'Property Sale',
            'idempotency_key' => uniqid('test_', true),
        ];

        $transaction = $this->transactionService->createTransaction($data, $this->teller->id);

        $this->assertInstanceOf(Transaction::class, $transaction);
        // Transactions >= 50,000 get Pending status (requires manager approval)
        // Transactions < 50,000 but with hold requirements get OnHold status
        $this->assertTrue(
            in_array($transaction->status, [TransactionStatus::Pending, TransactionStatus::OnHold]),
            'Transaction should be held or pending for compliance review'
        );
        $this->assertEquals(CddLevel::Enhanced, $transaction->cdd_level);
        $this->assertNotNull($transaction->hold_reason);
    }

    public function test_transaction_without_till_balance_throws_exception(): void
    {
        // Close the till
        $this->tillBalance->update(['closed_at' => now()]);

        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '100.00',
            'rate' => '4.500000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Till is not open for this currency');

        $this->transactionService->createTransaction($data, $this->teller->id);
    }

    public function test_transaction_with_invalid_ip_throws_exception(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '100.00',
            'rate' => '4.500000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IP address format');

        $this->transactionService->createTransaction($data, $this->teller->id, 'invalid-ip');
    }

    public function test_transaction_amount_precision(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '99.999999',
            'rate' => '4.123456',
            'purpose' => 'Test Precision',
            'source_of_funds' => 'Test',
            'idempotency_key' => uniqid('test_', true),
        ];

        $transaction = $this->transactionService->createTransaction($data, $this->teller->id);

        // Verify precision is maintained
        $this->assertStringContainsString('.', $transaction->amount_local);
        $this->assertGreaterThan(0, strlen(explode('.', $transaction->amount_local)[1] ?? ''));
    }

    public function test_transaction_creates_audit_log(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '100.00',
            'rate' => '4.500000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
            'idempotency_key' => uniqid('test_', true),
        ];

        $transaction = $this->transactionService->createTransaction($data, $this->teller->id);

        // Verify audit log was created
        $this->assertDatabaseHas('system_logs', [
            'action' => 'transaction_created',
            'user_id' => $this->teller->id,
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
        ]);
    }

    public function test_idempotency_key_prevents_duplicate(): void
    {
        $idempotencyKey = uniqid('test_', true);

        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '100.00',
            'rate' => '4.500000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
            'idempotency_key' => $idempotencyKey,
        ];

        // Create first transaction
        $transaction1 = $this->transactionService->createTransaction($data, $this->teller->id);

        // Attempt to create duplicate with same key
        $transaction2 = $this->transactionService->createTransaction($data, $this->teller->id);

        // Should return the same transaction
        $this->assertEquals($transaction1->id, $transaction2->id);
    }

    public function test_transaction_updates_till_balance(): void
    {
        $initialTotal = $this->tillBalance->transaction_total;

        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '100.00',
            'rate' => '4.500000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
            'idempotency_key' => uniqid('test_', true),
        ];

        $this->transactionService->createTransaction($data, $this->teller->id);

        // Refresh till balance
        $this->tillBalance->refresh();

        // Till balance should be updated
        $this->assertNotEquals($initialTotal, $this->tillBalance->transaction_total);
        $this->assertEquals('450.0000', $this->tillBalance->transaction_total);
    }

    public function test_transaction_assigns_correct_cdd_level(): void
    {
        // Test Simplified CDD (< RM 3,000)
        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '100.00',
            'rate' => '4.500000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
            'idempotency_key' => uniqid('test_', true),
        ];

        $transaction = $this->transactionService->createTransaction($data, $this->teller->id);
        $this->assertEquals(CddLevel::Simplified, $transaction->cdd_level);

        // Test Standard CDD (RM 3,000 - 49,999)
        $data['amount_foreign'] = '1000.00'; // 1000 * 4.5 = 4500 MYR
        $data['idempotency_key'] = uniqid('test_', true);

        $transaction2 = $this->transactionService->createTransaction($data, $this->teller->id);
        $this->assertEquals(CddLevel::Standard, $transaction2->cdd_level);
    }

    public function test_transaction_with_pep_customer_gets_enhanced_cdd(): void
    {
        // Mark customer as PEP
        $this->customer->update(['pep_status' => true]);

        $data = [
            'customer_id' => $this->customer->id,
            'till_id' => $this->counter->id,
            'type' => TransactionType::Buy->value,
            'currency_code' => $this->currency->code,
            'amount_foreign' => '100.00',
            'rate' => '4.500000',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
            'idempotency_key' => uniqid('test_', true),
        ];

        $transaction = $this->transactionService->createTransaction($data, $this->teller->id);

        $this->assertEquals(CddLevel::Enhanced, $transaction->cdd_level);
        $this->assertEquals(TransactionStatus::OnHold, $transaction->status);
    }
}
