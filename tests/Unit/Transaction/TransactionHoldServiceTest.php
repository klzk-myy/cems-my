<?php

namespace Tests\Unit\Transaction;

use App\Enums\CddLevel;
use App\Services\Transaction\TransactionHoldService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionHoldServiceTest extends TestCase
{
    protected TransactionHoldService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransactionHoldService;
    }

    #[Test]
    public function enhanced_cdd_requires_hold(): void
    {
        $result = $this->service->requiresHold(CddLevel::Enhanced, []);

        $this->assertTrue($result);
    }

    #[Test]
    public function standard_cdd_no_hold_by_default(): void
    {
        $result = $this->service->requiresHold(CddLevel::Standard, []);

        $this->assertFalse($result);
    }

    #[Test]
    public function simplified_cdd_no_hold_by_default(): void
    {
        $result = $this->service->requiresHold(CddLevel::Simplified, []);

        $this->assertFalse($result);
    }

    #[Test]
    public function hold_triggered_by_critical_risk_flag(): void
    {
        $riskFlags = [
            ['type' => 'velocity', 'severity' => 'medium'],
            ['type' => 'structuring', 'severity' => 'critical'],
        ];

        $result = $this->service->requiresHold(CddLevel::Standard, $riskFlags);

        $this->assertTrue($result);
    }

    #[Test]
    public function hold_not_triggered_by_non_critical_flags(): void
    {
        $riskFlags = [
            ['type' => 'velocity', 'severity' => 'low'],
            ['type' => 'round_trip', 'severity' => 'medium'],
        ];

        $result = $this->service->requiresHold(CddLevel::Standard, $riskFlags);

        $this->assertFalse($result);
    }

    #[Test]
    public function get_hold_reasons_includes_enhanced_cdd(): void
    {
        $reasons = $this->service->getHoldReasons(CddLevel::Enhanced, []);

        $this->assertContains('Enhanced CDD requires hold', $reasons);
    }

    #[Test]
    public function get_hold_reasons_includes_critical_flags(): void
    {
        $riskFlags = [
            ['type' => 'structuring', 'severity' => 'critical'],
        ];

        $reasons = $this->service->getHoldReasons(CddLevel::Standard, $riskFlags);

        $this->assertContains('Critical risk: structuring', $reasons);
    }

    #[Test]
    public function get_hold_reasons_only_contains_enhanced_and_critical(): void
    {
        $reasons = $this->service->getHoldReasons(CddLevel::Standard, [
            ['type' => 'structuring', 'severity' => 'critical'],
        ]);

        $this->assertEquals(1, count($reasons));
        $this->assertContains('Critical risk: structuring', $reasons);
    }

    #[Test]
    public function no_duplicate_reasons_when_enhanced_and_critical(): void
    {
        $reasons = $this->service->getHoldReasons(CddLevel::Enhanced, [
            ['type' => 'structuring', 'severity' => 'critical'],
        ]);

        $this->assertEquals(2, count($reasons)); // "Enhanced CDD" + "Critical risk: structuring"
    }
}
