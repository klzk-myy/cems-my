<?php

namespace Tests\Unit\Enums;

use App\Enums\ComplianceCaseType;
use PHPUnit\Framework\TestCase;

class ComplianceCaseTypeTest extends TestCase
{
    public function test_all_case_types_exist(): void
    {
        $expectedTypes = ['Investigation', 'Edd', 'Str', 'SanctionReview', 'Counterfeit'];

        $actualCases = array_column(ComplianceCaseType::cases(), 'name');

        foreach ($expectedTypes as $type) {
            $this->assertContains($type, $actualCases, "ComplianceCaseType::$type should exist");
        }
    }

    public function test_requires_str_link_only_for_str(): void
    {
        $this->assertFalse(ComplianceCaseType::Investigation->requiresStrLink());
        $this->assertFalse(ComplianceCaseType::Edd->requiresStrLink());
        $this->assertTrue(ComplianceCaseType::Str->requiresStrLink());
        $this->assertFalse(ComplianceCaseType::SanctionReview->requiresStrLink());
        $this->assertFalse(ComplianceCaseType::Counterfeit->requiresStrLink());
    }

    public function test_requires_edd_link_only_for_edd(): void
    {
        $this->assertFalse(ComplianceCaseType::Investigation->requiresEddLink());
        $this->assertTrue(ComplianceCaseType::Edd->requiresEddLink());
        $this->assertFalse(ComplianceCaseType::Str->requiresEddLink());
        $this->assertFalse(ComplianceCaseType::SanctionReview->requiresEddLink());
        $this->assertFalse(ComplianceCaseType::Counterfeit->requiresEddLink());
    }

    public function test_default_sla_hours(): void
    {
        $this->assertEquals(120, ComplianceCaseType::Investigation->defaultSlaHours());
        $this->assertEquals(72, ComplianceCaseType::Edd->defaultSlaHours());
        $this->assertEquals(24, ComplianceCaseType::Str->defaultSlaHours());
        $this->assertEquals(24, ComplianceCaseType::SanctionReview->defaultSlaHours());
        $this->assertEquals(24, ComplianceCaseType::Counterfeit->defaultSlaHours());
    }

    public function test_label_returns_human_readable_labels(): void
    {
        $this->assertEquals('Investigation', ComplianceCaseType::Investigation->label());
        $this->assertEquals('Enhanced Due Diligence', ComplianceCaseType::Edd->label());
        $this->assertEquals('Suspicious Transaction Report', ComplianceCaseType::Str->label());
        $this->assertEquals('Sanction Review', ComplianceCaseType::SanctionReview->label());
        $this->assertEquals('Counterfeit', ComplianceCaseType::Counterfeit->label());
    }
}
