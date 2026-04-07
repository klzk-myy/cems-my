<?php

namespace Tests\Unit\Enums;

use App\Enums\ComplianceCasePriority;
use PHPUnit\Framework\TestCase;

class ComplianceCasePriorityTest extends TestCase
{
    public function test_all_priorities_exist(): void
    {
        $expectedPriorities = ['Low', 'Medium', 'High', 'Critical'];

        $actualCases = array_column(ComplianceCasePriority::cases(), 'name');

        foreach ($expectedPriorities as $priority) {
            $this->assertContains($priority, $actualCases, "ComplianceCasePriority::$priority should exist");
        }
    }

    public function test_weight_returns_correct_values(): void
    {
        $this->assertEquals(1, ComplianceCasePriority::Low->weight());
        $this->assertEquals(2, ComplianceCasePriority::Medium->weight());
        $this->assertEquals(3, ComplianceCasePriority::High->weight());
        $this->assertEquals(4, ComplianceCasePriority::Critical->weight());
    }

    public function test_color_returns_bootstrap_colors(): void
    {
        $this->assertEquals('info', ComplianceCasePriority::Low->color());
        $this->assertEquals('warning', ComplianceCasePriority::Medium->color());
        $this->assertEquals('danger', ComplianceCasePriority::High->color());
        $this->assertEquals('dark', ComplianceCasePriority::Critical->color());
    }

    public function test_label_returns_human_readable_labels(): void
    {
        $this->assertEquals('Low', ComplianceCasePriority::Low->label());
        $this->assertEquals('Medium', ComplianceCasePriority::Medium->label());
        $this->assertEquals('High', ComplianceCasePriority::High->label());
        $this->assertEquals('Critical', ComplianceCasePriority::Critical->label());
    }
}
