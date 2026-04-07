<?php

namespace Tests\Unit\Enums;

use App\Enums\ComplianceCaseStatus;
use PHPUnit\Framework\TestCase;

class ComplianceCaseStatusTest extends TestCase
{
    public function test_all_statuses_exist(): void
    {
        $expectedStatuses = ['Open', 'UnderReview', 'PendingApproval', 'Closed', 'Escalated'];

        $actualCases = array_column(ComplianceCaseStatus::cases(), 'name');

        foreach ($expectedStatuses as $status) {
            $this->assertContains($status, $actualCases, "ComplianceCaseStatus::$status should exist");
        }
    }

    public function test_can_move_to_valid_transitions(): void
    {
        // Open -> UnderReview, Closed, Escalated
        $this->assertTrue(ComplianceCaseStatus::Open->canMoveTo(ComplianceCaseStatus::UnderReview));
        $this->assertTrue(ComplianceCaseStatus::Open->canMoveTo(ComplianceCaseStatus::Closed));
        $this->assertTrue(ComplianceCaseStatus::Open->canMoveTo(ComplianceCaseStatus::Escalated));
        $this->assertFalse(ComplianceCaseStatus::Open->canMoveTo(ComplianceCaseStatus::PendingApproval));

        // UnderReview -> PendingApproval, Closed, Escalated
        $this->assertTrue(ComplianceCaseStatus::UnderReview->canMoveTo(ComplianceCaseStatus::PendingApproval));
        $this->assertTrue(ComplianceCaseStatus::UnderReview->canMoveTo(ComplianceCaseStatus::Closed));
        $this->assertTrue(ComplianceCaseStatus::UnderReview->canMoveTo(ComplianceCaseStatus::Escalated));
        $this->assertFalse(ComplianceCaseStatus::UnderReview->canMoveTo(ComplianceCaseStatus::Open));

        // PendingApproval -> Closed, UnderReview
        $this->assertTrue(ComplianceCaseStatus::PendingApproval->canMoveTo(ComplianceCaseStatus::Closed));
        $this->assertTrue(ComplianceCaseStatus::PendingApproval->canMoveTo(ComplianceCaseStatus::UnderReview));
        $this->assertFalse(ComplianceCaseStatus::PendingApproval->canMoveTo(ComplianceCaseStatus::Open));
        $this->assertFalse(ComplianceCaseStatus::PendingApproval->canMoveTo(ComplianceCaseStatus::Escalated));

        // Escalated -> UnderReview, Closed
        $this->assertTrue(ComplianceCaseStatus::Escalated->canMoveTo(ComplianceCaseStatus::UnderReview));
        $this->assertTrue(ComplianceCaseStatus::Escalated->canMoveTo(ComplianceCaseStatus::Closed));
        $this->assertFalse(ComplianceCaseStatus::Escalated->canMoveTo(ComplianceCaseStatus::Open));
        $this->assertFalse(ComplianceCaseStatus::Escalated->canMoveTo(ComplianceCaseStatus::PendingApproval));

        // Closed -> none
        $this->assertFalse(ComplianceCaseStatus::Closed->canMoveTo(ComplianceCaseStatus::Open));
        $this->assertFalse(ComplianceCaseStatus::Closed->canMoveTo(ComplianceCaseStatus::UnderReview));
        $this->assertFalse(ComplianceCaseStatus::Closed->canMoveTo(ComplianceCaseStatus::PendingApproval));
        $this->assertFalse(ComplianceCaseStatus::Closed->canMoveTo(ComplianceCaseStatus::Escalated));
    }

    public function test_is_terminal_only_for_closed(): void
    {
        $this->assertFalse(ComplianceCaseStatus::Open->isTerminal());
        $this->assertFalse(ComplianceCaseStatus::UnderReview->isTerminal());
        $this->assertFalse(ComplianceCaseStatus::PendingApproval->isTerminal());
        $this->assertTrue(ComplianceCaseStatus::Closed->isTerminal());
        $this->assertFalse(ComplianceCaseStatus::Escalated->isTerminal());
    }

    public function test_is_active_for_all_except_closed(): void
    {
        $this->assertTrue(ComplianceCaseStatus::Open->isActive());
        $this->assertTrue(ComplianceCaseStatus::UnderReview->isActive());
        $this->assertTrue(ComplianceCaseStatus::PendingApproval->isActive());
        $this->assertFalse(ComplianceCaseStatus::Closed->isActive());
        $this->assertTrue(ComplianceCaseStatus::Escalated->isActive());
    }

    public function test_label_returns_human_readable_labels(): void
    {
        $this->assertEquals('Open', ComplianceCaseStatus::Open->label());
        $this->assertEquals('Under Review', ComplianceCaseStatus::UnderReview->label());
        $this->assertEquals('Pending Approval', ComplianceCaseStatus::PendingApproval->label());
        $this->assertEquals('Closed', ComplianceCaseStatus::Closed->label());
        $this->assertEquals('Escalated', ComplianceCaseStatus::Escalated->label());
    }

    public function test_color_returns_bootstrap_colors(): void
    {
        $this->assertEquals('primary', ComplianceCaseStatus::Open->color());
        $this->assertEquals('warning', ComplianceCaseStatus::UnderReview->color());
        $this->assertEquals('info', ComplianceCaseStatus::PendingApproval->color());
        $this->assertEquals('success', ComplianceCaseStatus::Closed->color());
        $this->assertEquals('danger', ComplianceCaseStatus::Escalated->color());
    }
}
