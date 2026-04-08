<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\TransactionStatus;
use App\Models\SystemLog;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Services\ComplianceService;
use App\Services\CurrencyPositionService;
use App\Services\MathService;
use App\Services\TransactionMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionApprovalController extends Controller
{
    public function __construct(
        protected CurrencyPositionService $positionService,
        protected ComplianceService $complianceService,
        protected TransactionMonitoringService $monitoringService,
        protected MathService $mathService
    ) {}

    /**
     * Approve a pending transaction.
     */
    public function approve(Request $request, int $transactionId): JsonResponse
    {
        $transaction = Transaction::findOrFail($transactionId);

        if (! $transaction->status->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not pending approval.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Re-evaluate AML rules before approval
            $amlResult = $this->monitoringService->monitorTransaction($transaction);
            $highPriorityFlags = array_filter($amlResult['flags'], function ($flag) {
                return $flag->flag_type->isHighPriority();
            });

            if (! empty($highPriorityFlags)) {
                DB::rollBack();
                $flagTypes = implode(', ', array_map(fn ($f) => $f->flag_type->label(), $highPriorityFlags));

                return response()->json([
                    'success' => false,
                    'message' => "Approval blocked: High-priority AML flags generated ({$flagTypes}).",
                ], 422);
            }

            // Optimistic locking
            $updated = Transaction::where('id', $transaction->id)
                ->where('status', TransactionStatus::Pending)
                ->where('version', $transaction->version)
                ->update([
                    'status' => TransactionStatus::Completed,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'version' => DB::raw('version + 1'),
                ]);

            if (! $updated) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Transaction was already processed or modified.',
                ], 409);
            }

            $transaction->refresh();

            // Update positions
            $tillBalance = TillBalance::where('till_id', $transaction->till_id ?? 'MAIN')
                ->where('currency_code', $transaction->currency_code)
                ->whereDate('date', today())
                ->whereNull('closed_at')
                ->first();

            if ($tillBalance) {
                $this->positionService->updatePosition(
                    $transaction->currency_code,
                    (string) $transaction->amount_foreign,
                    (string) $transaction->rate,
                    $transaction->type->value,
                    $transaction->till_id ?? 'MAIN'
                );
            }

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'transaction_approved',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'new_values' => ['approved_by' => auth()->id()],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction approved successfully.',
                'data' => $transaction->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Approval failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
