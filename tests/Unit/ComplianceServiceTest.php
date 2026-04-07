<?php

namespace Tests\Unit;

use App\Enums\CddLevel;
use App\Enums\ComplianceFlagType;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ComplianceService;
use App\Services\EncryptionService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComplianceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ComplianceService $service;
    protected User $user;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComplianceService(
            new EncryptionService,
            new MathService
        );

        // Create required related records for transactions
        $this->user = User::factory()->create();
        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$']
        );
    }

    protected function createCustomer(array $attributes = []): Customer
    {
        $defaults = [
            'full_name' => 'John Doe',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789012'),
            'nationality' => 'Malaysian',
            'date_of_birth' => '1990-01-01',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ];

        return Customer::create(array_merge($defaults, $attributes));
    }

    protected function createTransaction(array $attributes = []): Transaction
    {
        $defaults = [
            'customer_id' => $this->createCustomer()->id,
            'user_id' => $this->user->id,
            'type' => TransactionType::Buy,
            'currency_code' => $this->currency->code,
            'amount_local' => 1000,
            'amount_foreign' => 100,
            'rate' => 10.00,
            'cdd_level' => CddLevel::Simplified,
            'status' => TransactionStatus::Completed,
            'created_at' => now(),
        ];

        return Transaction::create(array_merge($defaults, $attributes));
    }

    public function test_simplified_cdd_for_small_amounts()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John Low Risk',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ]);

        $level = $this->service->determineCDDLevel('1000', $customer);
        $this->assertEquals(CddLevel::Simplified, $level);
    }

    public function test_standard_cdd_for_medium_amounts()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John Standard',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ]);

        $level = $this->service->determineCDDLevel('5000', $customer);
        $this->assertEquals(CddLevel::Standard, $level);
    }

    public function test_enhanced_cdd_for_large_amounts()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John Large',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ]);

        $level = $this->service->determineCDDLevel('60000', $customer);
        $this->assertEquals(CddLevel::Enhanced, $level);
    }

    public function test_enhanced_cdd_for_pep()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John PEP',
            'pep_status' => true,
            'risk_rating' => 'Low',
        ]);

        $level = $this->service->determineCDDLevel('1000', $customer);
        $this->assertEquals(CddLevel::Enhanced, $level);
    }

    public function test_enhanced_cdd_for_high_risk_customer()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John High Risk',
            'pep_status' => false,
            'risk_rating' => 'High',
        ]);

        $level = $this->service->determineCDDLevel('1000', $customer);
        $this->assertEquals(CddLevel::Enhanced, $level);
    }

    public function test_requires_hold_for_large_amounts()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John Large',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ]);

        $result = $this->service->requiresHold('60000', $customer);
        $this->assertTrue($result['requires_hold']);
        $this->assertContains(ComplianceFlagType::EddRequired->value, $result['reasons']);
    }

    public function test_requires_hold_for_pep_status()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John PEP',
            'pep_status' => true,
            'risk_rating' => 'Low',
        ]);

        $result = $this->service->requiresHold('1000', $customer);
        $this->assertTrue($result['requires_hold']);
        $this->assertContains(ComplianceFlagType::PepStatus->value, $result['reasons']);
    }

    public function test_requires_hold_for_high_risk_customer()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John High Risk',
            'pep_status' => false,
            'risk_rating' => 'High',
        ]);

        $result = $this->service->requiresHold('1000', $customer);
        $this->assertTrue($result['requires_hold']);
        $this->assertContains(ComplianceFlagType::HighRiskCustomer->value, $result['reasons']);
    }

    public function test_requires_hold_for_sanction_match()
    {
        // Create sanction list first
        $listId = DB::table('sanction_lists')->insertGetId([
            'name' => 'Test Sanction List',
            'list_type' => 'Internal',
            'uploaded_by' => $this->user->id,
            'is_active' => true,
        ]);

        // Insert directly via DB to create sanction entry
        DB::table('sanction_entries')->insert([
            'list_id' => $listId,
            'entity_name' => 'John Sanctioned',
            'entity_type' => 'Individual',
            'aliases' => 'Johnny S',
        ]);

        $customer = $this->createCustomer([
            'full_name' => 'John Sanctioned',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ]);

        $result = $this->service->requiresHold('1000', $customer);
        $this->assertTrue($result['requires_hold']);
        $this->assertContains(ComplianceFlagType::SanctionMatch->value, $result['reasons']);
    }

    public function test_check_sanction_match_finds_match()
    {
        // Create sanction list first
        $listId = DB::table('sanction_lists')->insertGetId([
            'name' => 'Test Sanction List',
            'list_type' => 'Internal',
            'uploaded_by' => $this->user->id,
            'is_active' => true,
        ]);

        // Insert directly via DB to create sanction entry
        DB::table('sanction_entries')->insert([
            'list_id' => $listId,
            'entity_name' => 'John Sanctioned',
            'entity_type' => 'Individual',
            'aliases' => 'Johnny S',
        ]);

        $customer = $this->createCustomer([
            'full_name' => 'John Sanctioned',
        ]);

        $hasMatch = $this->service->checkSanctionMatch($customer);
        $this->assertTrue($hasMatch);
    }

    public function test_check_sanction_match_no_match()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John Clean',
        ]);

        $hasMatch = $this->service->checkSanctionMatch($customer);
        $this->assertFalse($hasMatch);
    }

    public function test_check_velocity_tracks_24h_transactions()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John Velocity',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ]);

        // Create transaction 10 hours ago
        Transaction::create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'type' => TransactionType::Buy,
            'currency_code' => $this->currency->code,
            'amount_local' => 30000,
            'amount_foreign' => 3000,
            'rate' => 10.00,
            'cdd_level' => CddLevel::Standard,
            'status' => TransactionStatus::Completed,
            'created_at' => now()->subHours(10),
        ]);

        // Create transaction 5 hours ago
        Transaction::create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'type' => TransactionType::Buy,
            'currency_code' => $this->currency->code,
            'amount_local' => 15000,
            'amount_foreign' => 1500,
            'rate' => 10.00,
            'cdd_level' => CddLevel::Standard,
            'status' => TransactionStatus::Completed,
            'created_at' => now()->subHours(5),
        ]);

        $result = $this->service->checkVelocity($customer->id, '6000');

        // 30000 + 15000 = 45000 (without new transaction)
        $this->assertEquals('45000', $result['amount_24h']);
        // With new transaction: 45000 + 6000 = 51000, which exceeds 50000 threshold
        $this->assertTrue($result['threshold_exceeded']);
    }

    public function test_check_velocity_below_threshold()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John Low Velocity',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ]);

        // Create small transaction
        Transaction::create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'type' => TransactionType::Buy,
            'currency_code' => $this->currency->code,
            'amount_local' => 5000,
            'amount_foreign' => 500,
            'rate' => 10.00,
            'cdd_level' => CddLevel::Simplified,
            'status' => TransactionStatus::Completed,
            'created_at' => now()->subHours(5),
        ]);

        $result = $this->service->checkVelocity($customer->id, '5000');

        // 5000 (only one small transaction)
        $this->assertEquals('5000', $result['amount_24h']);
        // With new transaction: 5000 + 5000 = 10000, which does not exceed 50000 threshold
        $this->assertFalse($result['threshold_exceeded']);
    }

    public function test_check_structuring_detects_multiple_small_transactions()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John Structuring',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ]);

        // Create 3 small transactions within an hour
        for ($i = 0; $i < 3; $i++) {
            Transaction::create([
                'customer_id' => $customer->id,
                'user_id' => $this->user->id,
                'type' => TransactionType::Buy,
                'currency_code' => $this->currency->code,
                'amount_local' => 2000, // Under 3000
                'amount_foreign' => 200,
                'rate' => 10.00,
                'cdd_level' => CddLevel::Simplified,
                'status' => TransactionStatus::Completed,
                'created_at' => now()->subMinutes(30),
            ]);
        }

        $hasStructuring = $this->service->checkStructuring($customer->id);
        $this->assertTrue($hasStructuring);
    }

    public function test_check_structuring_no_issue_with_normal_transactions()
    {
        $customer = $this->createCustomer([
            'full_name' => 'John Normal',
            'pep_status' => false,
            'risk_rating' => 'Low',
        ]);

        // Create only 2 small transactions
        for ($i = 0; $i < 2; $i++) {
            Transaction::create([
                'customer_id' => $customer->id,
                'user_id' => $this->user->id,
                'type' => TransactionType::Buy,
                'currency_code' => $this->currency->code,
                'amount_local' => 2000,
                'amount_foreign' => 200,
                'rate' => 10.00,
                'cdd_level' => CddLevel::Simplified,
                'status' => TransactionStatus::Completed,
                'created_at' => now()->subMinutes(30),
            ]);
        }

        $hasStructuring = $this->service->checkStructuring($customer->id);
        $this->assertFalse($hasStructuring);
    }

    public function test_requires_ctos_for_large_cash_transaction()
    {
        $result = $this->service->requiresCtos('15000', 'Buy');
        $this->assertTrue($result);

        $result = $this->service->requiresCtos('9999', 'Buy');
        $this->assertFalse($result);
    }

    public function test_requires_ctos_for_both_buy_and_sell()
    {
        $resultBuy = $this->service->requiresCtos('15000', 'Buy');
        $resultSell = $this->service->requiresCtos('15000', 'Sell');

        $this->assertTrue($resultBuy);
        $this->assertTrue($resultSell);
    }
}
