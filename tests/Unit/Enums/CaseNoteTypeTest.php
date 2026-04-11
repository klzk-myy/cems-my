<?php

namespace Tests\Unit\Enums;

use App\Enums\CaseNoteType;
use PHPUnit\Framework\TestCase;

class CaseNoteTypeTest extends TestCase
{
    public function test_all_note_types_exist(): void
    {
        $expectedTypes = ['Investigation', 'Update', 'Decision', 'Escalation'];
        $actualCases = array_column(CaseNoteType::cases(), 'name');

        foreach ($expectedTypes as $type) {
            $this->assertContains($type, $actualCases, "CaseNoteType::$type should exist");
        }
    }

    public function test_label_returns_human_readable_labels(): void
    {
        $this->assertEquals('Investigation', CaseNoteType::Investigation->label());
        $this->assertEquals('Update', CaseNoteType::Update->label());
        $this->assertEquals('Decision', CaseNoteType::Decision->label());
        $this->assertEquals('Escalation', CaseNoteType::Escalation->label());
    }

    public function test_icon_returns_fontawesome_classes(): void
    {
        $this->assertEquals('fa-search', CaseNoteType::Investigation->icon());
        $this->assertEquals('fa-edit', CaseNoteType::Update->icon());
        $this->assertEquals('fa-gavel', CaseNoteType::Decision->icon());
        $this->assertEquals('fa-arrow-alt-circle-up', CaseNoteType::Escalation->icon());
    }
}
