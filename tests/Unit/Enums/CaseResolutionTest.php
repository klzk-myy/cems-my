<?php

namespace Tests\Unit\Enums;

use App\Enums\CaseResolution;
use PHPUnit\Framework\TestCase;

class CaseResolutionTest extends TestCase
{
    public function test_all_resolutions_exist(): void
    {
        $expectedResolutions = ['NoConcern', 'WarningIssued', 'EddRequired', 'StrFiled', 'ClosedNoAction'];

        $actualCases = array_column(CaseResolution::cases(), 'name');

        foreach ($expectedResolutions as $resolution) {
            $this->assertContains($resolution, $actualCases, "CaseResolution::$resolution should exist");
        }
    }

    public function test_label_returns_human_readable_labels(): void
    {
        $this->assertEquals('No Concern', CaseResolution::NoConcern->label());
        $this->assertEquals('Warning Issued', CaseResolution::WarningIssued->label());
        $this->assertEquals('EDD Required', CaseResolution::EddRequired->label());
        $this->assertEquals('STR Filed', CaseResolution::StrFiled->label());
        $this->assertEquals('Closed - No Action', CaseResolution::ClosedNoAction->label());
    }

    public function test_requires_str_only_for_str_filed(): void
    {
        $this->assertFalse(CaseResolution::NoConcern->requiresStr());
        $this->assertFalse(CaseResolution::WarningIssued->requiresStr());
        $this->assertFalse(CaseResolution::EddRequired->requiresStr());
        $this->assertTrue(CaseResolution::StrFiled->requiresStr());
        $this->assertFalse(CaseResolution::ClosedNoAction->requiresStr());
    }

    public function test_requires_edd_only_for_edd_required(): void
    {
        $this->assertFalse(CaseResolution::NoConcern->requiresEdd());
        $this->assertFalse(CaseResolution::WarningIssued->requiresEdd());
        $this->assertTrue(CaseResolution::EddRequired->requiresEdd());
        $this->assertFalse(CaseResolution::StrFiled->requiresEdd());
        $this->assertFalse(CaseResolution::ClosedNoAction->requiresEdd());
    }
}
