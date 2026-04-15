<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\TellerAllocation;
use App\Models\User;
use Exception;

class CounterOpeningWorkflowService
{
    public function __construct(
        protected BranchPoolService $branchPoolService,
        protected TellerAllocationService $tellerAllocationService,
        protected CounterService $counterService,
    ) {}

    public function initiateOpeningRequest(User $teller, Counter $counter, array $requestedAmounts): array
    {
        $branch = $teller->branch;

        $requests = [];
        foreach ($requestedAmounts as $currency => $amount) {
            $pool = $this->branchPoolService->getOrCreateForBranch($branch, $currency);

            if (! $pool->hasAvailable($amount)) {
                throw new Exception("Insufficient {$currency} balance in branch pool. Available: {$pool->available_balance}");
            }

            $allocation = $this->tellerAllocationService->requestAllocation(
                $teller,
                $teller,
                $currency,
                $amount,
                null,
                $counter
            );

            $requests[] = $allocation;
        }

        return $requests;
    }

    public function approveAndOpen(User $manager, Counter $counter, User $teller, array $approvedAmounts, array $dailyLimits = []): CounterSession
    {
        $today = now()->toDateString();

        $tellerAllocations = [];
        foreach ($approvedAmounts as $currency => $amount) {
            $allocation = TellerAllocation::where('user_id', $teller->id)
                ->where('currency_code', $currency)
                ->where('session_date', $today)
                ->where('status', 'pending')
                ->first();

            if (! $allocation) {
                throw new Exception("No pending allocation found for {$currency}");
            }

            $dailyLimit = $dailyLimits[$currency] ?? null;
            $this->tellerAllocationService->approveAllocation($allocation, $manager, $amount, $dailyLimit);
            $this->tellerAllocationService->activateAllocation($allocation);

            $tellerAllocations[] = $allocation;
        }

        $openingFloats = collect($tellerAllocations)->mapWithKeys(fn ($a) => [$a->currency_code => [
            'currency_id' => $a->currency_code,
            'amount' => $a->current_balance,
        ]])->toArray();

        $session = $this->counterService->openSession($counter, $teller, $openingFloats);

        foreach ($tellerAllocations as $allocation) {
            $allocation->update(['counter_id' => $counter->id]);
        }

        return $session;
    }

    public function getPendingRequestsForBranch(Branch $branch): array
    {
        return $this->tellerAllocationService->getPendingAllocationsForBranch($branch)
            ->groupBy(fn ($a) => $a->user_id);
    }
}
