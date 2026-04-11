<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CddLevel;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\Branch;
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
 * API V1 Transaction Controller Tests
 *
 * Tests the API endpoints for transaction creation and retrieval.
 */
class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $tellerUser;
    protected User $managerUser;
    protected Customer $customer;
    protected Currency $currency;
    protected TillBalance $tillBalance;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create branch first
        $this->branch = Branch::create([
            'code' => 'HQ',
            'name' => 'Head Office',
            'type' => 'head_office',
            'country' => 'Malaysia',
            'is_active' => true,
            'is_main' => true,
        ]);

        // Create teller user with branch
        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);

        // Create manager user with branch
        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
            'branch_id' => $this->branch->id,
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

        // Open till with branch
        $this->tillBalance = TillBalance::create([
            'till_id' => 'MAIN',
            'currency_code' => 'USD',
            'opening_balance' => '10000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
            'branch_id' => $this->branch->id,
        ]);

        // Create accounting period for journal entries
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

    /**
     * Test listing transactions returns proper JSON structure.
     */
    public function test_index_returns_proper_json_structure(): void
    {
        // Create a completed transaction
        Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'branch_id' => $this->branch->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '100.00',
            'amount_local' => '472.00',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
            'status' => TransactionStatus::Completed,
            'cdd_level' => CddLevel::Simplified,
            'version' => 0,
        ]);

        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->getJson('/api/v1/transactions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
        $response->assertJson(['success' => true]);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    /**
     * Test creating a buy transaction via API.
     */
    public function test_create_buy_transaction_succeeds(): void
    {
        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->postJson('/api/v1/transactions', [
                'customer_id' => $this->customer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'amount_foreign' => '100.00',
                'rate' => '4.7200',
                'purpose' => 'Travel',
                'source_of_funds' => 'Salary',
                'till_id' => 'MAIN',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'customer_id',
                'user_id',
                'branch_id',
                'till_id',
                'type',
                'currency_code',
                'amount_foreign',
                'amount_local',
                'rate',
                'purpose',
                'source_of_funds',
                'status',
                'cdd_level',
            ],
        ]);
        $response->assertJson([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'data' => [
                'customer_id' => $this->customer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'status' => 'Completed',
            ],
        ]);

        // Verify transaction was actually created in database
        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Buy',
            'currency_code' => 'USD',
        ]);
    }

    /**
     * Test creating a sell transaction via API.
     */
    public function test_create_sell_transaction_succeeds(): void
    {
        // First create a currency position (buy some USD first)
        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '1000.00',
            'avg_cost_rate' => '4.7200',
            'last_valuation_rate' => '4.7500',
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->postJson('/api/v1/transactions', [
                'customer_id' => $this->customer->id,
                'type' => 'Sell',
                'currency_code' => 'USD',
                'amount_foreign' => '100.00',
                'rate' => '4.7500',
                'purpose' => 'Travel',
                'source_of_funds' => 'Salary',
                'till_id' => 'MAIN',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'type' => 'Sell',
                'currency_code' => 'USD',
            ],
        ]);

        // Verify transaction was actually created
        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'Sell',
            'currency_code' => 'USD',
        ]);
    }

    /**
     * Test idempotency key prevents duplicate transactions.
     */
    public function test_idempotency_key_prevents_duplicate(): void
    {
        $idempotencyKey = 'test-idempotency-'.uniqid();

        // First request
        $response1 = $this->actingAs($this->tellerUser, 'sanctum')
            ->postJson('/api/v1/transactions', [
                'customer_id' => $this->customer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'amount_foreign' => '100.00',
                'rate' => '4.7200',
                'purpose' => 'Travel',
                'source_of_funds' => 'Salary',
                'till_id' => 'MAIN',
                'idempotency_key' => $idempotencyKey,
            ]);

        $response1->assertStatus(201);
        $transactionId1 = $response1->json('data.id');

        // Second request with same idempotency key should return same transaction
        $response2 = $this->actingAs($this->tellerUser, 'sanctum')
            ->postJson('/api/v1/transactions', [
                'customer_id' => $this->customer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'amount_foreign' => '100.00',
                'rate' => '4.7200',
                'purpose' => 'Travel',
                'source_of_funds' => 'Salary',
                'till_id' => 'MAIN',
                'idempotency_key' => $idempotencyKey,
            ]);

        $response2->assertStatus(201);
        $transactionId2 = $response2->json('data.id');

        // Should be the same transaction
        $this->assertEquals($transactionId1, $transactionId2);

        // Only one transaction should exist
        $this->assertEquals(1, Transaction::where('idempotency_key', $idempotencyKey)->count());
    }

    /**
     * Test creating transaction without open till fails.
     */
    public function test_create_transaction_without_open_till_fails(): void
    {
        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->postJson('/api/v1/transactions', [
                'customer_id' => $this->customer->id,
                'type' => 'Buy',
                'currency_code' => 'EUR', // Different currency, no till open
                'amount_foreign' => '100.00',
                'rate' => '5.5000',
                'purpose' => 'Travel',
                'source_of_funds' => 'Salary',
                'till_id' => 'MAIN',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Test creating sell transaction without sufficient stock fails.
     */
    public function test_create_sell_without_sufficient_stock_fails(): void
    {
        // No position created, so sell should fail

        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->postJson('/api/v1/transactions', [
                'customer_id' => $this->customer->id,
                'type' => 'Sell',
                'currency_code' => 'USD',
                'amount_foreign' => '100.00',
                'rate' => '4.7500',
                'purpose' => 'Travel',
                'source_of_funds' => 'Salary',
                'till_id' => 'MAIN',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Test validation errors return proper structure.
     */
    public function test_validation_errors_return_proper_structure(): void
    {
        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->postJson('/api/v1/transactions', [
                // Missing required fields
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'customer_id',
            'type',
            'currency_code',
            'amount_foreign',
            'rate',
            'purpose',
            'source_of_funds',
            'till_id',
        ]);
    }

    /**
     * Test getting a single transaction.
     */
    public function test_show_returns_proper_json_structure(): void
    {
        $transaction = Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'branch_id' => $this->branch->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '100.00',
            'amount_local' => '472.00',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Salary',
            'status' => TransactionStatus::Completed,
            'cdd_level' => CddLevel::Simplified,
            'version' => 0,
        ]);

        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->getJson("/api/v1/transactions/{$transaction->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'customer_id',
                'user_id',
                'branch_id',
                'till_id',
                'type',
                'currency_code',
                'amount_foreign',
                'amount_local',
                'rate',
                'purpose',
                'source_of_funds',
                'status',
                'cdd_level',
                'created_at',
                'updated_at',
                'customer' => [
                    'id',
                    'full_name',
                ],
                'user' => [
                    'id',
                    'username',
                ],
            ],
        ]);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $transaction->id,
                'type' => 'Buy',
            ],
        ]);
    }

    /**
     * Test getting non-existent transaction returns 404.
     */
    public function test_show_non_existent_transaction_returns_404(): void
    {
        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->getJson('/api/v1/transactions/99999');

        $response->assertStatus(404);
    }

    /**
     * Test unauthorized access returns 401.
     */
    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson('/api/v1/transactions');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/transactions', []);
        $response->assertStatus(401);
    }

    /**
     * Test that CDD level is properly calculated for large transactions.
     */
    public function test_cdd_level_calculated_for_large_amount(): void
    {
        // Create a high-risk customer
        $highRiskCustomer = Customer::create([
            'full_name' => 'High Risk Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('999999999999'),
            'date_of_birth' => '1980-01-01',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('456 High Risk Street'),
            'contact_number_encrypted' => encrypt('0199999999'),
            'email' => 'highrisk@test.com',
            'pep_status' => true, // PEP customer
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'High',
        ]);

        // Large transaction >= RM 50,000
        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->postJson('/api/v1/transactions', [
                'customer_id' => $highRiskCustomer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'amount_foreign' => '10600.00', // ~50,000 MYR at 4.72 rate
                'rate' => '4.7200',
                'purpose' => 'Investment',
                'source_of_funds' => 'Savings',
                'till_id' => 'MAIN',
            ]);

        $response->assertStatus(201);
        // Large PEP transaction should be pending approval
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'Pending',
            ],
        ]);
    }

    /**
     * Test monetary values are stored as strings (BCMath precision).
     */
    public function test_monetary_values_precision(): void
    {
        $response = $this->actingAs($this->tellerUser, 'sanctum')
            ->postJson('/api/v1/transactions', [
                'customer_id' => $this->customer->id,
                'type' => 'Buy',
                'currency_code' => 'USD',
                'amount_foreign' => '123.4567',
                'rate' => '4.123456',
                'purpose' => 'Test',
                'source_of_funds' => 'Salary',
                'till_id' => 'MAIN',
            ]);

        $response->assertStatus(201);

        // Verify the amount_local is calculated correctly with precision
        // Note: MathService uses scale 6, then decimal(18,4) rounds to 4 places
        // 123.4567 * 4.123456 = 509.068279..., rounded to 4dp = 509.0683
        $transaction = Transaction::find($response->json('data.id'));
        $this->assertEquals('509.0683', $transaction->amount_local);
    }
}
