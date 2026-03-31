<?php

namespace App\Services;

use App\Models\FlaggedTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionMonitoringService
{
    protected ComplianceService $complianceService;
    protected MathService $mathService;

    public function __construct(
        ComplianceService $complianceService,
        MathService $mathService
    ) {
        $this->complianceService = $complianceService;
        $this->mathService = $mathService;
    }

    public function monitorTransaction(Transaction $transaction): array
    {
        $flags = [];

        // Rule 1: 24h Velocity Check
        $velocityCheck = $this->complianceService->checkVelocity(
            $transaction->customer_id,
            $transaction->amount_local
        );
        if ($velocityCheck['threshold_exceeded']) {
            $flags[] = $this->createFlag($transaction, 'Velocity', "24h velocity exceeded: RM {$velocityCheck['with_new_transaction']}");
        }

        // Rule 2: Structuring Detection
        if ($this->complianceService->checkStructuring($transaction->customer_id)) {
            $flags[] = $this->createFlag($transaction, 'Structuring', 'Potential structuring: 3+ transactions under RM 3,000 within 1 hour');
        }

        // Rule 3: Unusual Pattern
        if ($this->isUnusualPattern($transaction)) {
            $flags[] = $this->createFlag($transaction, 'Manual', 'Transaction deviates 200% from customer average');
        }

        // Rule 4: EDD Threshold
        $holdCheck = $this->complianceService->requiresHold(
            $transaction->amount_local,
            $transaction->customer
        );
        if ($holdCheck['requires_hold']) {
            $transaction->update(['status' => 'OnHold']);
            foreach ($holdCheck['reasons'] as $reason) {
                $flags[] = $this->createFlag($transaction, 'EDD_Required', $reason);
            }
        }

        return [
            'transaction_id' => $transaction->id,
            'flags_created' => count($flags),
            'flags' => $flags,
            'status' => $transaction->status,
        ];
    }

    protected function isUnusualPattern(Transaction $transaction): bool
    {
        $customerAvg = Transaction::where('customer_id', $transaction->customer_id)
            ->where('created_at', '>=', now()->subDays(90))
            ->avg('amount_local');

        if (!$customerAvg || $customerAvg == 0) {
            return false;
        }

        $deviation = $this->mathService->divide(
            (string) $transaction->amount_local,
            (string) $customerAvg
        );

        return $this->mathService->compare($deviation, '2') > 0;
    }

    protected function createFlag(Transaction $transaction, string $type, string $reason): FlaggedTransaction
    {
        return FlaggedTransaction::create([
            'transaction_id' => $transaction->id,
            'flag_type' => $type,
            'flag_reason' => $reason,
            'status' => 'Open',
        ]);
    }

    public function getOpenFlags(): array
    {
        return FlaggedTransaction::where('status', 'Open')
            ->with(['transaction.customer', 'assignedTo'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function assignFlag(int $flagId, int $userId): bool
    {
        return FlaggedTransaction::where('id', $flagId)
            ->update([
                'assigned_to' => $userId,
                'status' => 'Under_Review',
            ]);
    }

    public function resolveFlag(int $flagId, int $userId, ?string $notes = null): bool
    {
        return FlaggedTransaction::where('id', $flagId)
            ->update([
                'reviewed_by' => $userId,
                'notes' => $notes,
                'status' => 'Resolved',
                'resolved_at' => now(),
            ]);
    }
}
