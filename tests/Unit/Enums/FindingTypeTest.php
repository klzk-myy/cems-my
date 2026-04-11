<?php

namespace Tests\Unit\Enums;

use App\Enums\FindingType;
use PHPUnit\Framework\TestCase;

class FindingTypeTest extends TestCase
{
    public function test_all_finding_types_exist(): void
    {
        $expectedTypes = [
            'VelocityExceeded',
            'StructuringPattern',
            'AggregateTransaction',
            'StrDeadline',
            'SanctionMatch',
            'LocationAnomaly',
            'CurrencyFlowAnomaly',
            'CounterfeitAlert',
            'RiskScoreChange',
        ];

        $actualCases = array_column(FindingType::cases(), 'name');

        foreach ($expectedTypes as $type) {
            $this->assertContains(
                $type,
                $actualCases,
                "FindingType::$type should exist"
            );
        }
    }

    public function test_label_returns_human_readable_labels(): void
    {
        $this->assertEquals('Velocity Exceeded', FindingType::VelocityExceeded->label());
        $this->assertEquals('Structuring Pattern', FindingType::StructuringPattern->label());
        $this->assertEquals('Aggregate Transaction', FindingType::AggregateTransaction->label());
        $this->assertEquals('STR Deadline', FindingType::StrDeadline->label());
        $this->assertEquals('Sanction Match', FindingType::SanctionMatch->label());
        $this->assertEquals('Location Anomaly', FindingType::LocationAnomaly->label());
        $this->assertEquals('Currency Flow Anomaly', FindingType::CurrencyFlowAnomaly->label());
        $this->assertEquals('Counterfeit Alert', FindingType::CounterfeitAlert->label());
        $this->assertEquals('Risk Score Change', FindingType::RiskScoreChange->label());
    }

    public function test_default_severity_for_critical_types(): void
    {
        $this->assertEquals('Critical', FindingType::SanctionMatch->defaultSeverity()->name);
        $this->assertEquals('Critical', FindingType::CounterfeitAlert->defaultSeverity()->name);
    }

    public function test_default_severity_for_high_types(): void
    {
        $this->assertEquals('High', FindingType::VelocityExceeded->defaultSeverity()->name);
        $this->assertEquals('High', FindingType::StructuringPattern->defaultSeverity()->name);
    }

    public function test_default_severity_for_medium_types(): void
    {
        $this->assertEquals('Medium', FindingType::AggregateTransaction->defaultSeverity()->name);
        $this->assertEquals('Medium', FindingType::StrDeadline->defaultSeverity()->name);
    }

    public function test_default_severity_for_low_types(): void
    {
        $this->assertEquals('Low', FindingType::LocationAnomaly->defaultSeverity()->name);
        $this->assertEquals('Low', FindingType::CurrencyFlowAnomaly->defaultSeverity()->name);
        $this->assertEquals('Low', FindingType::RiskScoreChange->defaultSeverity()->name);
    }
}
