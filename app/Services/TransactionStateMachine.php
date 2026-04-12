<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Transaction State Machine
 *
 * Manages the 10-state lifecycle of currency exchange transactions.
 * Enforces valid state transitions and maintains transition history.
 */
class TransactionStateMachine
{
    /**
     * Valid state transitions map.
     * Key is the current state, value is an array of valid target states.
     */
    protected const TRANSITIONS = [
        'Draft' => [
            'PendingApproval',
            'Cancelled',
        ],
        'PendingApproval' => [
            'Approved',
            'Rejected',
            'Cancelled',
        ],
        'Pending' => [           // Large transaction awaiting manager approval
            'Approved',
            'OnHold',
            'Cancelled',
        ],
        'Approved' => [
            'Processing',
            'Cancelled',
        ],
        'Processing' => [
            'Completed',
            'Failed',
            'Cancelled',
        ],
        'Completed' => [
            'Finalized',
            'Reversed',
            'Cancelled',
        ],
        'Finalized' => [],
        'Cancelled' => [],
        'Reversed' => [],
        'Failed' => [
            'PendingApproval',
            'Pending',
            'Cancelled',
        ],
        'Rejected' => [
            'Cancelled',
        ],
        'OnHold' => [            // Transaction on hold awaiting compliance review
            'Pending',
            'Approved',
            'Cancelled',
        ],
    ];

    /**
     * Transition history stored on the transaction.
     */
    protected array $history = [];

    /**
     * Create a new TransactionStateMachine instance.
     *
     * @param  Transaction  $transaction  The transaction to manage
     */
    public function __construct(protected Transaction $transaction)
    {
        $this->loadHistory();
    }

    /**
     * Load transition history from the transaction.
     */
    protected function loadHistory(): void
    {
        $this->history = $this->transaction->transition_history ?? [];
    }

    /**
     * Check if a transition to the given status is valid.
     *
     * @param  TransactionStatus  $to  The target status
     * @return bool True if the transition is valid
     */
    public function canTransitionTo(TransactionStatus $to): bool
    {
        $from = $this->transaction->status->value;
        $validTransitions = self::TRANSITIONS[$from] ?? [];

        return in_array($to->value, $validTransitions, true);
    }

    /**
     * Transition the transaction to a new status.
     *
     * @param  TransactionStatus  $to  The target status
     * @param  array  $context  Optional context (reason, user_id, etc.)
     * @return bool True if the transition was successful
     */
    public function transitionTo(TransactionStatus $to, array $context = []): bool
    {
        if (! $this->canTransitionTo($to)) {
            Log::warning('Invalid state transition attempted', [
                'transaction_id' => $this->transaction->id,
                'from' => $this->transaction->status->value,
                'to' => $to->value,
                'context' => $context,
            ]);

            return false;
        }

        $from = $this->transaction->status;
        $now = now();

        // Record the transition in history
        $this->history[] = [
            'from' => $from->value,
            'to' => $to->value,
            'reason' => $context['reason'] ?? null,
            'user_id' => $context['user_id'] ?? auth()->id(),
            'timestamp' => $now->toIso8601String(),
        ];

        // Update the transaction
        $this->transaction->status = $to;
        $this->transaction->transition_history = $this->history;

        // Set additional fields based on transition
        $this->applyTransitionMetadata($from, $to, $context);

        return $this->transaction->save();
    }

    /**
     * Apply metadata fields based on the transition.
     *
     * @param  TransactionStatus  $from  The previous status
     * @param  TransactionStatus  $to  The new status
     * @param  array  $context  Transition context
     */
    protected function applyTransitionMetadata(
        TransactionStatus $from,
        TransactionStatus $to,
        array $context
    ): void {
        // Track approval
        if ($to === TransactionStatus::Approved) {
            $this->transaction->approved_by = $context['user_id'] ?? auth()->id();
            $this->transaction->approved_at = now();
        }

        // Track cancellation
        if ($to === TransactionStatus::Cancelled) {
            $this->transaction->cancelled_at = now();
            $this->transaction->cancelled_by = $context['user_id'] ?? auth()->id();
            $this->transaction->cancellation_reason = $context['reason'] ?? null;
        }

        // Track failure reason
        if ($to === TransactionStatus::Failed) {
            $this->transaction->failure_reason = $context['reason'] ?? null;
        }

        // Track rejection
        if ($to === TransactionStatus::Rejected) {
            $this->transaction->rejection_reason = $context['reason'] ?? null;
        }

        // Track reversal
        if ($to === TransactionStatus::Reversed) {
            $this->transaction->reversal_reason = $context['reason'] ?? null;
        }
    }

    /**
     * Get available transitions from the current state.
     *
     * @return array Array of valid TransactionStatus values
     */
    public function getAvailableTransitions(): array
    {
        $currentValue = $this->transaction->status->value;
        $validTransitions = self::TRANSITIONS[$currentValue] ?? [];

        return array_map(
            fn (string $value) => TransactionStatus::from($value),
            $validTransitions
        );
    }

    /**
     * Get the transition history for this transaction.
     *
     * @return array Array of transition records
     */
    public function getTransitionHistory(): array
    {
        return $this->history;
    }

    /**
     * Submit a draft transaction for approval.
     * Draft -> PendingApproval
     *
     * @return bool True if transition was successful
     */
    public function submit(): bool
    {
        return $this->transitionTo(TransactionStatus::PendingApproval);
    }

    /**
     * Approve a pending transaction.
     * PendingApproval -> Approved
     *
     * @return bool True if transition was successful
     */
    public function approve(): bool
    {
        return $this->transitionTo(TransactionStatus::Approved);
    }

    /**
     * Reject a pending transaction.
     * PendingApproval -> Rejected
     *
     * @param  string  $reason  The reason for rejection
     * @return bool True if transition was successful
     */
    public function reject(string $reason): bool
    {
        return $this->transitionTo(TransactionStatus::Rejected, ['reason' => $reason]);
    }

    /**
     * Start processing an approved transaction.
     * Approved -> Processing
     *
     * @return bool True if transition was successful
     */
    public function startProcessing(): bool
    {
        return $this->transitionTo(TransactionStatus::Processing);
    }

    /**
     * Complete a processing transaction.
     * Processing -> Completed
     *
     * @return bool True if transition was successful
     */
    public function complete(): bool
    {
        return $this->transitionTo(TransactionStatus::Completed);
    }

    /**
     * Mark a processing transaction as failed.
     * Processing -> Failed
     *
     * @param  string  $reason  The reason for failure
     * @return bool True if transition was successful
     */
    public function fail(string $reason): bool
    {
        return $this->transitionTo(TransactionStatus::Failed, ['reason' => $reason]);
    }

    /**
     * Retry a failed transaction.
     * Failed -> PendingApproval
     *
     * @return bool True if transition was successful
     */
    public function retry(): bool
    {
        // retry() is only valid from Failed state
        if (! $this->transaction->status->isFailed()) {
            return false;
        }

        return $this->transitionTo(TransactionStatus::PendingApproval);
    }

    /**
     * Reverse a completed transaction.
     * Completed -> Reversed
     *
     * @param  string  $reason  The reason for reversal
     * @return bool True if transition was successful
     */
    public function reverse(string $reason): bool
    {
        return $this->transitionTo(TransactionStatus::Reversed, ['reason' => $reason]);
    }

    /**
     * Finalize a completed transaction.
     * Completed -> Finalized
     *
     * @return bool True if transition was successful
     */
    public function finalize(): bool
    {
        return $this->transitionTo(TransactionStatus::Finalized);
    }

    /**
     * Cancel the transaction.
     * Any valid state -> Cancelled (with guards based on current state)
     *
     * @param  string  $reason  The reason for cancellation
     * @return bool True if transition was successful
     */
    public function cancel(string $reason): bool
    {
        return $this->transitionTo(TransactionStatus::Cancelled, ['reason' => $reason]);
    }

    /**
     * Place transaction on hold (compliance review required).
     * Pending -> OnHold
     *
     * @param  string  $reason  The reason for hold
     * @return bool True if transition was successful
     */
    public function hold(string $reason): bool
    {
        return $this->transitionTo(TransactionStatus::OnHold, ['reason' => $reason]);
    }

    /**
     * Release transaction from hold back to pending.
     * OnHold -> Pending (re-submit for approval)
     *
     * @return bool True if transition was successful
     */
    public function release(): bool
    {
        // release() is only valid from OnHold state
        if (! $this->transaction->status->isOnHold()) {
            return false;
        }

        return $this->transitionTo(TransactionStatus::Pending);
    }

    /**
     * Approve a pending transaction (large transaction flow).
     * Pending -> Approved
     *
     * @return bool True if transition was successful
     */
    public function approvePending(): bool
    {
        return $this->transitionTo(TransactionStatus::Approved);
    }

    /**
     * Force a status change (admin override).
     * Allows transitioning to any valid state regardless of normal flow.
     *
     * @param  TransactionStatus  $status  The target status
     * @param  string  $reason  The reason for the override
     * @return bool True if transition was successful
     */
    public function forceStatus(TransactionStatus $status, string $reason): bool
    {
        $from = $this->transaction->status;

        // Record the forced transition
        $this->history[] = [
            'from' => $from->value,
            'to' => $status->value,
            'reason' => $reason,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
            'forced' => true,
        ];

        $this->transaction->status = $status;
        $this->transaction->transition_history = $this->history;

        // Apply metadata based on forced status
        if ($status === TransactionStatus::Cancelled) {
            $this->transaction->cancelled_at = now();
            $this->transaction->cancelled_by = auth()->id();
            $this->transaction->cancellation_reason = $reason;
        }

        return $this->transaction->save();
    }
}
