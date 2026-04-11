<?php

namespace Tests\Unit\Enums;

use App\Enums\FindingSeverity;
use PHPUnit\Framework\TestCase;

class FindingSeverityTest extends TestCase
{
    public function test_all_severity_levels_exist(): void
    {
        $expectedLevels = ['Low', 'Medium', 'High', 'Critical'];
        $actualCases = array_column(FindingSeverity::cases(), 'name');

        foreach ($expectedLevels as $level) {
            $this->assertContains($level, $actualCases, "FindingSeverity::$level should exist");
        }
    }

    public function test_weight_ordering(): void
    {
        $this->assertEquals(1, FindingSeverity::Low->weight());
        $this->assertEquals(2, FindingSeverity::Medium->weight());
        $this->assertEquals(3, FindingSeverity::High->weight());
        $this->assertEquals(4, FindingSeverity::Critical->weight());
    }

    public function test_critical_is_higher_than_high(): void
    {
        $this->assertGreaterThan(
            FindingSeverity::High->weight(),
            FindingSeverity::Critical->weight()
        );
    }

    public function test_high_is_higher_than_medium(): void
    {
        $this->assertGreaterThan(
            FindingSeverity::Medium->weight(),
            FindingSeverity::High->weight()
        );
    }

    public function test_medium_is_higher_than_low(): void
    {
        $this->assertGreaterThan(
            FindingSeverity::Low->weight(),
            FindingSeverity::Medium->weight()
        );
    }

    public function test_color_returns_bootstrap_classes(): void
    {
        $this->assertEquals('success', FindingSeverity::Low->color());
        $this->assertEquals('warning', FindingSeverity::Medium->color());
        $this->assertEquals('danger', FindingSeverity::High->color());
        $this->assertEquals('dark', FindingSeverity::Critical->color());
    }

    public function test_icon_returns_fontawesome_classes(): void
    {
        $this->assertEquals('fa-info-circle text-success', FindingSeverity::Low->icon());
        $this->assertEquals('fa-exclamation-triangle text-warning', FindingSeverity::Medium->icon());
        $this->assertEquals('fa-exclamation-circle text-danger', FindingSeverity::High->icon());
        $this->assertEquals('fa-skull-crossbones text-dark', FindingSeverity::Critical->icon());
    }
}
