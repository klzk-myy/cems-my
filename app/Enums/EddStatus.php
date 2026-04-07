<?php

namespace App\Enums;

enum EddStatus: string
{
    case Incomplete = 'Incomplete';
    case PendingQuestionnaire = 'Pending_Questionnaire';
    case QuestionnaireSubmitted = 'Questionnaire_Submitted';
    case PendingReview = 'Pending_Review';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
    case Expired = 'Expired';

    public function label(): string
    {
        return match($this) {
            self::Incomplete => 'Incomplete',
            self::PendingQuestionnaire => 'Pending Questionnaire',
            self::QuestionnaireSubmitted => 'Questionnaire Submitted',
            self::PendingReview => 'Pending Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Incomplete => 'secondary',
            self::PendingQuestionnaire => 'info',
            self::QuestionnaireSubmitted => 'primary',
            self::PendingReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Expired => 'dark',
        };
    }

    public function canSubmitQuestionnaire(): bool
    {
        return $this === self::PendingQuestionnaire;
    }
}
