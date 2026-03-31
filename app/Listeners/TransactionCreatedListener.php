<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Services\TransactionMonitoringService;

class TransactionCreatedListener
{
    protected TransactionMonitoringService $monitoringService;

    public function __construct(TransactionMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    public function handle(TransactionCreated $event)
    {
        $this->monitoringService->monitorTransaction($event->transaction);
    }
}
