<?php

namespace Tests\Unit\Enums;

use App\Enums\FindingStatus;
use PHPUnit\Framework\TestCase;

class FindingStatusTest extends TestCase
{
    public function test_all_statuses_exist(): void
    {
        $expectedStatuses = ['New', 'Reviewed', 'Dismissed', 'CaseCreated'];
        $actualCases = array_column(FindingStatus::cases(), 'name');

        foreach ($expectedStatuses as $status) {
            $this->assertContains($status, $actualCases, "FindingStatus::$status should exist");
        }
    }

    public function test_can_be_reviewed(): void
    {
        // Only New can be reviewed
        $this->assertTrue(FindingStatus::New->canBeReviewed());
        $this->assertFalse(FindingStatus::Reviewed->canBeReviewed());
        $this->assertFalse(FindingStatus::Dismissed->canBeReviewed());
        $this->assertFalse(FindingStatus::CaseCreated->canBeReviewed());
    }

    public function test_can_be_dismissed(): void
    {
        // New and Reviewed can be dismissed
        $this->assertTrue(FindingStatus::New->canBeDismissed());
        $this->assertTrue(FindingStatus::Reviewed->canBeDismissed());
        $this->assertFalse(FindingStatus::Dismissed->canBeDismissed());
        $this->assertFalse(FindingStatus::CaseCreated->canBeDismissed());
    }

    public function test_can_create_case(): void
    {
        // New and Reviewed can create a case
        $this->assertTrue(FindingStatus::New->canCreateCase());
        $this->assertTrue(FindingStatus::Reviewed->canCreateCase());
        $this->assertFalse(FindingStatus::Dismissed->canCreateCase());
        $this->assertFalse(FindingStatus::CaseCreated->canCreateCase());
    }

    public function test_label_returns_human_readable_labels(): void
    {
        $this->assertEquals('New', FindingStatus::New->label());
        $this->assertEquals('Reviewed', FindingStatus::Reviewed->label());
        $this->assertEquals('Dismissed', FindingStatus::Dismissed->label());
        $this->assertEquals('Case Created', FindingStatus::CaseCreated->label());
    }
}
