<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ApprovalTask;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Approval Workflow Service
 *
 * Handles tiered approval thresholds for currency exchange transactions per BNM compliance.
 *
 * Thresholds:
 * - < RM 3,000: Auto-approve (no approval needed)
 * - RM 3,000 - 9,999.99: Supervisor approval required
 * - RM 10,000 - 49,999.99: Manager approval required
 * - >= RM 50,000: Admin approval required
 */
class ApprovalWorkflowService
{
    /**
     * Auto-approve threshold upper bound (exclusive).
     */
    public const AUTO_APPROVE_THRESHOLD = '3000';

    /**
     * Supervisor threshold upper bound (exclusive).
     */
    public const SUPERVISOR_THRESHOLD = '10000';

    /**
     * Manager threshold upper bound (exclusive).
     */
    public const MANAGER_THRESHOLD = '50000';

    /**
     * Default task expiration in hours.
     */
    public const DEFAULT_EXPIRATION_HOURS = 24;

    /**
     * Create a new ApprovalWorkflowService instance.
     */
    public function __construct(
        protected MathService $mathService
    ) {}

    /**
     * Check if a transaction requires approval based on amount.
     *
     * @param Transaction $transaction
     * @return bool True if approval is required
     */
    public function requiresApproval(Transaction $transaction): bool
    {
        return $this->getRequiredRole($transaction) !== 'none';
    }

    /**
     * Get the required role for approving a transaction.
     *
     * @param Transaction $transaction
     * @return string 'supervisor', 'manager', 'admin', or 'none'
     */
    public function getRequiredRole(Transaction $transaction): string
    {
        $amount = $transaction->amount_local;

        // Auto-approve: < RM 3,000
        if ($this->mathService->compare($amount, self::AUTO_APPROVE_THRESHOLD) < 0) {
            return 'none';
        }

        // Supervisor: RM 3,000 - 9,999.99
        if ($this->mathService->compare($amount, self::SUPERVISOR_THRESHOLD) < 0) {
            return 'supervisor';
        }

        // Manager: RM 10,000 - 49,999.99
        if ($this->mathService->compare($amount, self::MANAGER_THRESHOLD) < 0) {
            return 'manager';
        }

        // Admin: >= RM 50,000
        return 'admin';
    }

    /**
     * Get the threshold amount that triggered approval requirement.
     *
     * Returns the lower bound of the tier the transaction falls into.
     *
     * @param Transaction $transaction
     * @return string The threshold amount
     */
    public function getThresholdAmount(Transaction $transaction): string
    {
        $amount = $transaction->amount_local;

        // Auto-approve: < RM 3,000 (no threshold)
        if ($this->mathService->compare($amount, self::AUTO_APPROVE_THRESHOLD) < 0) {
            return '0.0000';
        }

        // Supervisor: RM 3,000+
        if ($this->mathService->compare($amount, self::SUPERVISOR_THRESHOLD) < 0) {
            return self::AUTO_APPROVE_THRESHOLD;
        }

        // Manager: RM 10,000+
        if ($this->mathService->compare($amount, self::MANAGER_THRESHOLD) < 0) {
            return self::SUPERVISOR_THRESHOLD;
        }

        // Admin: RM 50,000+
        return self::MANAGER_THRESHOLD;
    }

    /**
     * Create an approval task for a transaction.
     *
     * @param Transaction $transaction
     * @return ApprovalTask|null Null if no approval required
     */
    public function createApprovalTask(Transaction $transaction): ?ApprovalTask
    {
        $requiredRole = $this->getRequiredRole($transaction);

        if ($requiredRole === 'none') {
            return null;
        }

        $thresholdAmount = $this->getThresholdAmount($transaction);
        $expiresAt = now()->addHours(self::DEFAULT_EXPIRATION_HOURS);

        return ApprovalTask::create([
            'transaction_id' => $transaction->id,
            'status' => ApprovalTask::STATUS_PENDING,
            'threshold_amount' => $thresholdAmount,
            'required_role' => $requiredRole,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Approve an approval task.
     *
     * @param ApprovalTask $task
     * @param User $approver
     * @param string|null $notes
     * @return bool True if approval was successful
     */
    public function approve(ApprovalTask $task, User $approver, ?string $notes = null): bool
    {
        if (! $task->isActionable()) {
            Log::warning('ApprovalWorkflowService: Cannot approve non-actionable task', [
                'task_id' => $task->id,
                'status' => $task->status,
                'expires_at' => $task->expires_at,
            ]);
            return false;
        }

        if (! $this->canApprove($approver, $task->required_role)) {
            Log::warning('ApprovalWorkflowService: User lacks required role for approval', [
                'task_id' => $task->id,
                'user_id' => $approver->id,
                'user_role' => $approver->role->value,
                'required_role' => $task->required_role,
            ]);
            return false;
        }

        return $this->processDecision($task, $approver, ApprovalTask::STATUS_APPROVED, $notes);
    }

    /**
     * Reject an approval task.
     *
     * @param ApprovalTask $task
     * @param User $approver
     * @param string $reason The reason for rejection
     * @return bool True if rejection was successful
     */
    public function reject(ApprovalTask $task, User $approver, string $reason): bool
    {
        if (! $task->isActionable()) {
            Log::warning('ApprovalWorkflowService: Cannot reject non-actionable task', [
                'task_id' => $task->id,
                'status' => $task->status,
                'expires_at' => $task->expires_at,
            ]);
            return false;
        }

        if (! $this->canApprove($approver, $task->required_role)) {
            Log::warning('ApprovalWorkflowService: User lacks required role for rejection', [
                'task_id' => $task->id,
                'user_id' => $approver->id,
                'user_role' => $approver->role->value,
                'required_role' => $task->required_role,
            ]);
            return false;
        }

        $notes = "Rejected: {$reason}";

        return $this->processDecision($task, $approver, ApprovalTask::STATUS_REJECTED, $notes);
    }

    /**
     * Mark an approval task as expired.
     *
     * @param ApprovalTask $task
     * @return bool True if expiration was successful
     */
    public function expireTask(ApprovalTask $task): bool
    {
        if (! $task->isPending()) {
            return false;
        }

        $task->status = ApprovalTask::STATUS_EXPIRED;
        $task->decided_at = now();

        return $task->save();
    }

    /**
     * Get pending approval tasks that a user can act on.
     *
     * @param User $user
     * @return Collection<ApprovalTask>
     */
    public function getPendingTasksForUser(User $user): Collection
    {
        return ApprovalTask::getPendingForUser($user);
    }

    /**
     * Get the current approval status for a transaction.
     *
     * @param Transaction $transaction
     * @return array
     */
    public function getTransactionApprovalStatus(Transaction $transaction): array
    {
        $requiredRole = $this->getRequiredRole($transaction);
        $thresholdAmount = $this->getThresholdAmount($transaction);

        $task = ApprovalTask::where('transaction_id', $transaction->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'requires_approval' => $requiredRole !== 'none',
            'required_role' => $requiredRole,
            'threshold_amount' => $thresholdAmount,
            'has_pending_task' => $task?->isPending() ?? false,
            'task_status' => $task?->status,
            'task_id' => $task?->id,
            'task_created_at' => $task?->created_at?->toIso8601String(),
            'task_expires_at' => $task?->expires_at?->toIso8601String(),
            'decided_at' => $task?->decided_at?->toIso8601String(),
            'approver_id' => $task?->approver_id,
        ];
    }

    /**
     * Auto-approve a transaction if it qualifies (amount below threshold).
     *
     * @param Transaction $transaction
     * @return bool True if auto-approved, false if approval still required
     */
    public function autoApproveIfEligible(Transaction $transaction): bool
    {
        if ($this->requiresApproval($transaction)) {
            return false;
        }

        // For auto-approved transactions, we can create a record
        // or simply mark the transaction as approved directly
        // For now, we just return true indicating eligibility
        // The caller should handle the actual approval logic

        Log::info('ApprovalWorkflowService: Transaction auto-approved', [
            'transaction_id' => $transaction->id,
            'amount_local' => $transaction->amount_local,
        ]);

        return true;
    }

    /**
     * Check if a user can approve a task with the required role.
     *
     * @param User $user
     * @param string $requiredRole 'supervisor', 'manager', 'admin'
     * @return bool
     */
    protected function canApprove(User $user, string $requiredRole): bool
    {
        return match ($requiredRole) {
            'supervisor' => $user->role->isManager(), // Supervisors are managers in this system
            'manager' => $user->role->isManager(),
            'admin' => $user->role->isAdmin(),
            default => false,
        };
    }

    /**
     * Process an approval/rejection decision.
     *
     * @param ApprovalTask $task
     * @param User $approver
     * @param string $status
     * @param string|null $notes
     * @return bool
     */
    protected function processDecision(
        ApprovalTask $task,
        User $approver,
        string $status,
        ?string $notes
    ): bool {
        return DB::transaction(function () use ($task, $approver, $status, $notes) {
            $task->status = $status;
            $task->approver_id = $approver->id;
            $task->decided_at = now();

            if ($notes) {
                $task->notes = $notes;
            }

            $saved = $task->save();

            if ($saved) {
                Log::info('ApprovalWorkflowService: Task decision recorded', [
                    'task_id' => $task->id,
                    'status' => $status,
                    'approver_id' => $approver->id,
                    'transaction_id' => $task->transaction_id,
                ]);
            }

            return $saved;
        });
    }

    /**
     * Expire all tasks that have passed their expiration time.
     *
     * @return int Number of tasks expired
     */
    public function expireStaleTasks(): int
    {
        $count = 0;

        ApprovalTask::where('status', ApprovalTask::STATUS_PENDING)
            ->where('expires_at', '<=', now())
            ->chunk(100, function ($tasks) use (&$count) {
                foreach ($tasks as $task) {
                    if ($this->expireTask($task)) {
                        $count++;
                    }
                }
            });

        return $count;
    }
}
