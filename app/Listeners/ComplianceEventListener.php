<?php

namespace App\Listeners;

use App\Events\AlertCreated;
use App\Events\CaseOpened;
use App\Events\StrDraftGenerated;
use App\Services\CustomerRiskScoringService;
use App\Services\EddTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ComplianceEventListener implements ShouldQueue
{
    public function __construct(
        protected CustomerRiskScoringService $riskScoringService,
        protected EddTemplateService $eddTemplateService
    ) {}

    public function handleAlertCreated(AlertCreated $event): void
    {
        // Alert created - could trigger notification to compliance officers
    }

    public function handleCaseOpened(CaseOpened $event): void
    {
        $case = $event->case;

        // When a case is opened, recalculate customer risk score
        $this->riskScoringService->calculateAndSnapshot($case->customer_id);

        // Check if EDD should be prompted based on case priority
        if ($case->priority && in_array($case->priority->value, ['critical', 'high'])) {
            $this->eddTemplateService->getRecommendedTemplate([
                'transaction_amount' => $case->alerts->first()?->risk_score * 1000,
                'high_risk_country' => $case->alerts->contains(fn($a) => $a->type && $a->type->value === 'high_risk_country'),
            ]);
        }
    }

    public function handleStrDraftGenerated(StrDraftGenerated $event): void
    {
        // STR draft generated - could trigger notification to compliance manager
    }

    public function subscribe($events): array
    {
        return [
            AlertCreated::class => 'handleAlertCreated',
            CaseOpened::class => 'handleCaseOpened',
            StrDraftGenerated::class => 'handleStrDraftGenerated',
        ];
    }
}