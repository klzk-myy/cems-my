<?php

namespace Tests\Unit\Enums;

use App\Enums\EddStatus;
use PHPUnit\Framework\TestCase;

class EddStatusTest extends TestCase
{
    public function test_pending_questionnaire_exists(): void
    {
        $actualCases = array_column(EddStatus::cases(), 'name');
        $this->assertContains('PendingQuestionnaire', $actualCases);
    }

    public function test_questionnaire_submitted_exists(): void
    {
        $actualCases = array_column(EddStatus::cases(), 'name');
        $this->assertContains('QuestionnaireSubmitted', $actualCases);
    }

    public function test_expired_exists(): void
    {
        $actualCases = array_column(EddStatus::cases(), 'name');
        $this->assertContains('Expired', $actualCases);
    }

    public function test_can_submit_questionnaire(): void
    {
        // Only PendingQuestionnaire can submit questionnaire
        $this->assertTrue(EddStatus::PendingQuestionnaire->canSubmitQuestionnaire());
        $this->assertFalse(EddStatus::Incomplete->canSubmitQuestionnaire());
        $this->assertFalse(EddStatus::PendingReview->canSubmitQuestionnaire());
        $this->assertFalse(EddStatus::Approved->canSubmitQuestionnaire());
        $this->assertFalse(EddStatus::Rejected->canSubmitQuestionnaire());
        $this->assertFalse(EddStatus::Expired->canSubmitQuestionnaire());
    }
}
