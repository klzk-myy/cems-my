<?php

namespace Tests\Unit\Enums;

use App\Enums\RecalculationTrigger;
use PHPUnit\Framework\TestCase;

class RecalculationTriggerTest extends TestCase
{
    public function test_all_triggers_exist(): void
    {
        $expectedTriggers = ['Manual', 'Scheduled', 'EventDriven'];
        $actualCases = array_column(RecalculationTrigger::cases(), 'name');

        foreach ($expectedTriggers as $trigger) {
            $this->assertContains($trigger, $actualCases, "RecalculationTrigger::$trigger should exist");
        }
    }

    public function test_label_returns_human_readable_labels(): void
    {
        $this->assertEquals('Manual', RecalculationTrigger::Manual->label());
        $this->assertEquals('Scheduled', RecalculationTrigger::Scheduled->label());
        $this->assertEquals('Event-Driven', RecalculationTrigger::EventDriven->label());
    }
}
