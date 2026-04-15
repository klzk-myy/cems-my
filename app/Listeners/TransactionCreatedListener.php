<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Services\TransactionMonitoringService;
use App\Services\UnifiedRiskScoringService;

class TransactionCreatedListener
{
    protected TransactionMonitoringService $monitoringService;

    protected UnifiedRiskScoringService $riskScoringService;

    public function __construct(
        TransactionMonitoringService $monitoringService,
        UnifiedRiskScoringService $riskScoringService
    ) {
        $this->monitoringService = $monitoringService;
        $this->riskScoringService = $riskScoringService;
    }

    public function handle(TransactionCreated $event)
    {
        $this->monitoringService->monitorTransaction($event->transaction);
        $this->riskScoringService->calculateRiskScore($event->transaction->customer);
    }
}
