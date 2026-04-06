<?php

namespace Tests\Unit;

use App\Enums\AmlRuleType;
use App\Enums\TransactionStatus;
use App\Models\AmlRule;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\AmlRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmlRuleTest extends TestCase
{
    use RefreshDatabase;

    protected AmlRuleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AmlRuleService;
    }

    // ========================================
    // AmlRule Model Tests
    // ========================================

    public function test_aml_rule_can_be_created()
    {
        $rule = AmlRule::create([
            'rule_code' => 'TEST-001',
            'rule_name' => 'Test Rule',
            'description' => 'A test rule',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => [
                'window_hours' => 24,
                'max_transactions' => 10,
            ],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('aml_rules', [
            'rule_code' => 'TEST-001',
            'rule_name' => 'Test Rule',
            'is_active' => true,
        ]);
    }

    public function test_conditions_are_cast_to_array()
    {
        $rule = AmlRule::create([
            'rule_code' => 'TEST-002',
            'rule_name' => 'Test Rule',
            'rule_type' => AmlRuleType::AmountThreshold,
            'conditions' => [
                'min_amount' => 50000,
                'currency' => 'MYR',
            ],
            'action' => 'flag',
            'risk_score' => 20,
            'is_active' => true,
        ]);

        $this->assertIsArray($rule->conditions);
        $this->assertEquals(50000, $rule->conditions['min_amount']);
        $this->assertEquals('MYR', $rule->conditions['currency']);
    }

    public function test_rule_type_is_cast_to_enum()
    {
        $rule = AmlRule::create([
            'rule_code' => 'TEST-003',
            'rule_name' => 'Test Rule',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => ['window_hours' => 24, 'max_transactions' => 10],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(AmlRuleType::class, $rule->rule_type);
        $this->assertEquals(AmlRuleType::Velocity, $rule->rule_type);
    }

    public function test_active_scope_filters_inactive_rules()
    {
        AmlRule::create([
            'rule_code' => 'TEST-ACTIVE',
            'rule_name' => 'Active Rule',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => ['window_hours' => 24, 'max_transactions' => 10],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => true,
        ]);

        AmlRule::create([
            'rule_code' => 'TEST-INACTIVE',
            'rule_name' => 'Inactive Rule',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => ['window_hours' => 24, 'max_transactions' => 10],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => false,
        ]);

        $activeRules = AmlRule::active()->get();

        $this->assertCount(1, $activeRules);
        $this->assertEquals('TEST-ACTIVE', $activeRules->first()->rule_code);
    }

    public function test_by_type_scope_filters_rules()
    {
        AmlRule::create([
            'rule_code' => 'TEST-VEL',
            'rule_name' => 'Velocity Rule',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => ['window_hours' => 24, 'max_transactions' => 10],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => true,
        ]);

        AmlRule::create([
            'rule_code' => 'TEST-AMT',
            'rule_name' => 'Amount Rule',
            'rule_type' => AmlRuleType::AmountThreshold,
            'conditions' => ['min_amount' => 50000],
            'action' => 'flag',
            'risk_score' => 20,
            'is_active' => true,
        ]);

        $velocityRules = AmlRule::byType(AmlRuleType::Velocity)->get();

        $this->assertCount(1, $velocityRules);
        $this->assertEquals('TEST-VEL', $velocityRules->first()->rule_code);
    }

    // ========================================
    // Velocity Rule Tests
    // ========================================

    public function test_velocity_rule_triggers_when_transaction_count_exceeds_threshold()
    {
        $rule = AmlRule::create([
            'rule_code' => 'VEL-TEST',
            'rule_name' => 'Velocity Test',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => [
                'window_hours' => 24,
                'max_transactions' => 3, // Low threshold for testing
            ],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        // Create 3 existing transactions (threshold is 3, but current tx is excluded from count)
        // So we need 3 old + 1 new = 4 total, but only 3 are counted (>= 3 triggers)
        Transaction::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now()->subHours(1),
        ]);

        // Create new transaction
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertTrue($result['triggered']);
        $this->assertEquals(25, $result['risk_score']);
        $this->assertEquals('flag', $result['action']);
    }

    public function test_velocity_rule_does_not_trigger_when_below_threshold()
    {
        $rule = AmlRule::create([
            'rule_code' => 'VEL-TEST-LOW',
            'rule_name' => 'Low Velocity Test',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => [
                'window_hours' => 24,
                'max_transactions' => 10,
            ],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        // Create only 1 existing transaction
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now()->subHours(1),
        ]);

        // Create new transaction
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertFalse($result['triggered']);
    }

    public function test_velocity_rule_respects_time_window()
    {
        $rule = AmlRule::create([
            'rule_code' => 'VEL-TEST-WINDOW',
            'rule_name' => 'Velocity Window Test',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => [
                'window_hours' => 1, // 1 hour window
                'max_transactions' => 2,
            ],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        // Create 2 transactions 2 hours ago (outside window)
        Transaction::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now()->subHours(2),
        ]);

        // Create new transaction
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertFalse($result['triggered']);
    }

    // ========================================
    // Amount Threshold Rule Tests
    // ========================================

    public function test_amount_threshold_rule_triggers_on_large_transaction()
    {
        $rule = AmlRule::create([
            'rule_code' => 'AMT-TEST',
            'rule_name' => 'Amount Threshold Test',
            'rule_type' => AmlRuleType::AmountThreshold,
            'conditions' => [
                'min_amount' => 50000,
                'currency' => 'MYR',
            ],
            'action' => 'flag',
            'risk_score' => 20,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'amount_local' => 60000, // Above threshold
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertTrue($result['triggered']);
        $this->assertEquals(20, $result['risk_score']);
    }

    public function test_amount_threshold_rule_does_not_trigger_on_small_transaction()
    {
        $rule = AmlRule::create([
            'rule_code' => 'AMT-TEST-LOW',
            'rule_name' => 'Amount Threshold Low Test',
            'rule_type' => AmlRuleType::AmountThreshold,
            'conditions' => [
                'min_amount' => 50000,
                'currency' => 'MYR',
            ],
            'action' => 'flag',
            'risk_score' => 20,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'amount_local' => 5000, // Below threshold
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertFalse($result['triggered']);
    }

    // ========================================
    // Structuring Rule Tests
    // ========================================

    public function test_structuring_rule_detects_multiple_small_transactions()
    {
        $rule = AmlRule::create([
            'rule_code' => 'STR-TEST',
            'rule_name' => 'Structuring Test',
            'rule_type' => AmlRuleType::Structuring,
            'conditions' => [
                'window_days' => 1,
                'min_transaction_count' => 3,
                'aggregate_threshold' => 45000,
            ],
            'action' => 'hold',
            'risk_score' => 40,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        // Create 2 existing small transactions that sum with the new one to exceed threshold
        Transaction::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'amount_local' => 20000,
            'status' => TransactionStatus::Completed,
            'created_at' => now()->subHours(2),
        ]);

        // Create new transaction
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'amount_local' => 10000, // Total: 50000 (20000 + 20000 + 10000)
            'created_at' => now(),
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertTrue($result['triggered']);
        $this->assertEquals(40, $result['risk_score']);
        $this->assertEquals('hold', $result['action']);
    }

    public function test_structuring_rule_does_not_trigger_when_count_is_low()
    {
        $rule = AmlRule::create([
            'rule_code' => 'STR-TEST-LOW',
            'rule_name' => 'Structuring Low Test',
            'rule_type' => AmlRuleType::Structuring,
            'conditions' => [
                'window_days' => 1,
                'min_transaction_count' => 5, // High threshold
                'aggregate_threshold' => 45000,
            ],
            'action' => 'hold',
            'risk_score' => 40,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        // Only 2 existing transactions
        Transaction::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'amount_local' => 20000,
            'status' => TransactionStatus::Completed,
            'created_at' => now()->subHours(2),
        ]);

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'amount_local' => 10000,
            'created_at' => now(),
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertFalse($result['triggered']);
    }

    // ========================================
    // Frequency Rule Tests
    // ========================================

    public function test_frequency_rule_triggers_on_rapid_transactions()
    {
        $rule = AmlRule::create([
            'rule_code' => 'FREQ-TEST',
            'rule_name' => 'Frequency Test',
            'rule_type' => AmlRuleType::Frequency,
            'conditions' => [
                'window_hours' => 1,
                'max_transactions' => 5,
            ],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        // Create 5 transactions in the last hour
        Transaction::factory()->count(5)->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(30),
        ]);

        // Create new transaction
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertTrue($result['triggered']);
    }

    // ========================================
    // Geographic Rule Tests
    // ========================================

    public function test_geographic_rule_triggers_for_high_risk_country()
    {
        $rule = AmlRule::create([
            'rule_code' => 'GEO-TEST',
            'rule_name' => 'Geographic Test',
            'rule_type' => AmlRuleType::Geographic,
            'conditions' => [
                'countries' => ['IR', 'KP', 'SY'],
                'match_field' => 'customer_nationality',
            ],
            'action' => 'hold',
            'risk_score' => 50,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create([
            'nationality' => 'IR', // Iran - high risk
        ]);
        $user = \App\Models\User::factory()->create();

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertTrue($result['triggered']);
        $this->assertEquals(50, $result['risk_score']);
        $this->assertEquals('hold', $result['action']);
    }

    public function test_geographic_rule_does_not_trigger_for_low_risk_country()
    {
        $rule = AmlRule::create([
            'rule_code' => 'GEO-TEST-LOW',
            'rule_name' => 'Geographic Low Risk Test',
            'rule_type' => AmlRuleType::Geographic,
            'conditions' => [
                'countries' => ['IR', 'KP', 'SY'],
                'match_field' => 'customer_nationality',
            ],
            'action' => 'hold',
            'risk_score' => 50,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create([
            'nationality' => 'MY', // Malaysia - low risk
        ]);
        $user = \App\Models\User::factory()->create();

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertFalse($result['triggered']);
    }

    // ========================================
    // AmlRuleService Tests
    // ========================================

    public function test_evaluate_transaction_runs_all_active_rules()
    {
        // Create multiple rules
        AmlRule::create([
            'rule_code' => 'VEL-SVC',
            'rule_name' => 'Service Velocity Test',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => [
                'window_hours' => 24,
                'max_transactions' => 1,
            ],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => true,
        ]);

        AmlRule::create([
            'rule_code' => 'AMT-SVC',
            'rule_name' => 'Service Amount Test',
            'rule_type' => AmlRuleType::AmountThreshold,
            'conditions' => [
                'min_amount' => 50000,
            ],
            'action' => 'hold',
            'risk_score' => 20,
            'is_active' => true,
        ]);

        // Create inactive rule
        AmlRule::create([
            'rule_code' => 'INACTIVE-SVC',
            'rule_name' => 'Inactive Service Test',
            'rule_type' => AmlRuleType::Frequency,
            'conditions' => [
                'window_hours' => 1,
                'max_transactions' => 1,
            ],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => false,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        // Create old transaction for velocity rule (1 old + current = 2 >= 1 triggers)
        Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => now()->subHours(1),
        ]);

        // Create new transaction with high amount for amount threshold rule
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'amount_local' => 75000, // High enough to trigger 50000 threshold
        ]);

        $result = $this->service->evaluateTransaction($transaction);

        $this->assertTrue($result['triggered']);
        $this->assertEquals(2, count($result['rules_triggered']));
    }

    public function test_get_rules_for_transaction_filters_applicable_rules()
    {
        // Create geographic rule
        AmlRule::create([
            'rule_code' => 'GEO-SVC',
            'rule_name' => 'Service Geographic Test',
            'rule_type' => AmlRuleType::Geographic,
            'conditions' => [
                'countries' => ['IR'],
                'match_field' => 'customer_nationality',
            ],
            'action' => 'flag',
            'risk_score' => 50,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create(['nationality' => 'MY']);
        $user = \App\Models\User::factory()->create();

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
        ]);

        $rules = $this->service->getRulesForTransaction($transaction);

        // Geographic rule should not be applicable to Malaysian customer
        $this->assertTrue($rules->isEmpty());
    }

    public function test_inactive_rule_returns_not_triggered()
    {
        $rule = AmlRule::create([
            'rule_code' => 'INACTIVE-EVAL',
            'rule_name' => 'Inactive Eval Test',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => [
                'window_hours' => 24,
                'max_transactions' => 1,
            ],
            'action' => 'flag',
            'risk_score' => 25,
            'is_active' => false, // Inactive
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
        ]);

        $result = $rule->evaluate($transaction);

        $this->assertFalse($result['triggered']);
        $this->assertEquals(0, $result['risk_score']);
    }

    public function test_validate_conditions_for_velocity_rule()
    {
        $validConditions = [
            'window_hours' => 24,
            'max_transactions' => 10,
        ];

        $result = $this->service->validateConditions(AmlRuleType::Velocity, $validConditions);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_conditions_for_velocity_rule_missing_fields()
    {
        $invalidConditions = [
            'window_hours' => 24,
            // missing max_transactions
        ];

        $result = $this->service->validateConditions(AmlRuleType::Velocity, $invalidConditions);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_validate_conditions_for_geographic_rule()
    {
        $validConditions = [
            'countries' => ['IR', 'KP'],
            'match_field' => 'customer_nationality',
        ];

        $result = $this->service->validateConditions(AmlRuleType::Geographic, $validConditions);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_conditions_for_geographic_rule_empty_countries()
    {
        $invalidConditions = [
            'countries' => [], // Empty array
            'match_field' => 'customer_nationality',
        ];

        $result = $this->service->validateConditions(AmlRuleType::Geographic, $invalidConditions);

        $this->assertFalse($result['valid']);
        $this->assertContains('countries array cannot be empty', $result['errors']);
    }

    public function test_risk_score_is_capped_at_100()
    {
        // Create multiple rules that would sum to more than 100
        AmlRule::create([
            'rule_code' => 'VEL-CAP',
            'rule_name' => 'Velocity Cap Test',
            'rule_type' => AmlRuleType::Velocity,
            'conditions' => [
                'window_hours' => 24,
                'max_transactions' => 1,
            ],
            'action' => 'flag',
            'risk_score' => 50,
            'is_active' => true,
        ]);

        AmlRule::create([
            'rule_code' => 'AMT-CAP',
            'rule_name' => 'Amount Cap Test',
            'rule_type' => AmlRuleType::AmountThreshold,
            'conditions' => [
                'min_amount' => 1,
            ],
            'action' => 'flag',
            'risk_score' => 60, // Would make total 110
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $user = \App\Models\User::factory()->create();

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'amount_local' => 60000,
        ]);

        $result = $this->service->evaluateTransaction($transaction);

        $this->assertLessThanOrEqual(100, $result['total_risk_score']);
    }
}
