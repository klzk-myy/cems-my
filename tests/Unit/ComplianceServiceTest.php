<?php

namespace Tests\Unit;

use App\Enums\CddLevel;
use App\Enums\ComplianceFlagType;
use App\Models\Customer;
use App\Services\ComplianceService;
use App\Services\EncryptionService;
use App\Services\MathService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComplianceServiceTest extends TestCase
{
    protected ComplianceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComplianceService(
            new EncryptionService,
            new MathService
        );
    }

    public function test_simplified_cdd_for_small_amounts()
    {
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $customer = new Customer(['full_name' => 'John Doe', 'pep_status' => false, 'risk_rating' => 'Low']);
        $level = $this->service->determineCDDLevel('1000', $customer);
        $this->assertEquals(CddLevel::Simplified, $level);
    }

    public function test_standard_cdd_for_medium_amounts()
    {
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $customer = new Customer(['full_name' => 'John Doe', 'pep_status' => false, 'risk_rating' => 'Low']);
        $level = $this->service->determineCDDLevel('5000', $customer);
        $this->assertEquals(CddLevel::Standard, $level);
    }

    public function test_enhanced_cdd_for_large_amounts()
    {
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $customer = new Customer(['full_name' => 'John Doe', 'pep_status' => false, 'risk_rating' => 'Low']);
        $level = $this->service->determineCDDLevel('60000', $customer);
        $this->assertEquals(CddLevel::Enhanced, $level);
    }

    public function test_enhanced_cdd_for_pep()
    {
        // No DB mock needed - PEP check happens before sanction check
        $customer = new Customer(['full_name' => 'John Doe', 'pep_status' => true, 'risk_rating' => 'Low']);
        $level = $this->service->determineCDDLevel('1000', $customer);
        $this->assertEquals(CddLevel::Enhanced, $level);
    }

    public function test_requires_hold_for_large_amounts()
    {
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $customer = new Customer(['full_name' => 'John Doe', 'pep_status' => false, 'risk_rating' => 'Low']);
        $result = $this->service->requiresHold('60000', $customer);
        $this->assertTrue($result['requires_hold']);
        $this->assertContains(ComplianceFlagType::EddRequired->value, $result['reasons']);
    }

    public function test_enhanced_cdd_for_high_risk_customer()
    {
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $customer = new Customer(['full_name' => 'John Doe', 'pep_status' => false, 'risk_rating' => 'High']);
        $level = $this->service->determineCDDLevel('1000', $customer);
        $this->assertEquals(CddLevel::Enhanced, $level);
    }

    public function test_requires_hold_for_pep_status()
    {
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $customer = new Customer(['full_name' => 'John Doe', 'pep_status' => true, 'risk_rating' => 'Low']);
        $result = $this->service->requiresHold('1000', $customer);
        $this->assertTrue($result['requires_hold']);
        $this->assertContains(ComplianceFlagType::PepStatus->value, $result['reasons']);
    }

    public function test_requires_hold_for_high_risk_customer()
    {
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $customer = new Customer(['full_name' => 'John Doe', 'pep_status' => false, 'risk_rating' => 'High']);
        $result = $this->service->requiresHold('1000', $customer);
        $this->assertTrue($result['requires_hold']);
        $this->assertContains(ComplianceFlagType::HighRiskCustomer->value, $result['reasons']);
    }

    public function test_requires_hold_for_sanction_match()
    {
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(1);

        $customer = new Customer(['full_name' => 'John Doe', 'pep_status' => false, 'risk_rating' => 'Low']);
        $result = $this->service->requiresHold('1000', $customer);
        $this->assertTrue($result['requires_hold']);
        $this->assertContains(ComplianceFlagType::SanctionMatch->value, $result['reasons']);
    }

    public function test_check_sanction_match_finds_match()
    {
        DB::shouldReceive('table')->with('sanction_entries')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(1);

        $customer = new Customer(['full_name' => 'John Doe']);
        $hasMatch = $this->service->checkSanctionMatch($customer);
        $this->assertTrue($hasMatch);
    }

    public function test_check_sanction_match_no_match()
    {
        DB::shouldReceive('table')->with('sanction_entries')->andReturnSelf();
        DB::shouldReceive('whereRaw')->andReturnSelf();
        DB::shouldReceive('orWhereRaw')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $customer = new Customer(['full_name' => 'John Doe']);
        $hasMatch = $this->service->checkSanctionMatch($customer);
        $this->assertFalse($hasMatch);
    }
}
