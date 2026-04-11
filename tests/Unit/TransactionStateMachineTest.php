<?php

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected Transaction $transaction;

    protected TransactionStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();

        // Create minimal required related records
        $branch = Branch::factory()->create();
        $counter = Counter::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
        );

        $this->transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'till_id' => (string) $counter->id,
            'currency_code' => $currency->code,
            'type' => TransactionType::Buy,
            'status' => TransactionStatus::Draft,
        ]);

        $this->stateMachine = new TransactionStateMachine($this->transaction);
    }

    // =====================================================================
    // VALID TRANSITIONS
    // =====================================================================

    public function test_submit_transitions_draft_to_pending_approval(): void
    {
        $this->assertTrue($this->transaction->status->isDraft());
        $this->assertTrue($this->stateMachine->submit());
        $this->assertTrue($this->transaction->status->isPendingApproval());
    }

    public function test_approve_transitions_pending_approval_to_approved(): void
    {
        $this->transaction->update(['status' => TransactionStatus::PendingApproval]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->approve());
        $this->assertTrue($this->transaction->status->isApproved());
        $this->assertNotNull($this->transaction->approved_at);
    }

    public function test_reject_transitions_pending_approval_to_rejected(): void
    {
        $this->transaction->update(['status' => TransactionStatus::PendingApproval]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->reject('Insufficient documentation'));
        $this->assertTrue($this->transaction->status->isRejected());
        $this->assertEquals('Insufficient documentation', $this->transaction->rejection_reason);
    }

    public function test_start_processing_transitions_approved_to_processing(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Approved]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->startProcessing());
        $this->assertTrue($this->transaction->status->isProcessing());
    }

    public function test_complete_transitions_processing_to_completed(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Processing]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->complete());
        $this->assertTrue($this->transaction->status->isCompleted());
    }

    public function test_fail_transitions_processing_to_failed(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Processing]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->fail('Currency not in stock'));
        $this->assertTrue($this->transaction->status->isFailed());
        $this->assertEquals('Currency not in stock', $this->transaction->failure_reason);
    }

    public function test_retry_transitions_failed_to_pending_approval(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Failed]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->retry());
        $this->assertTrue($this->transaction->status->isPendingApproval());
    }

    public function test_reverse_transitions_completed_to_reversed(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Completed]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->reverse('Customer requested cancellation'));
        $this->assertTrue($this->transaction->status->isReversed());
        $this->assertEquals('Customer requested cancellation', $this->transaction->reversal_reason);
    }

    public function test_finalize_transitions_completed_to_finalized(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Completed]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->finalize());
        $this->assertTrue($this->transaction->status->isFinalized());
    }

    public function test_cancel_from_draft(): void
    {
        $this->assertTrue($this->stateMachine->cancel('Customer changed mind'));
        $this->assertTrue($this->transaction->status->isCancelled());
        $this->assertEquals('Customer changed mind', $this->transaction->cancellation_reason);
    }

    public function test_cancel_from_pending_approval(): void
    {
        $this->transaction->update(['status' => TransactionStatus::PendingApproval]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->cancel('Transaction no longer needed'));
        $this->assertTrue($this->transaction->status->isCancelled());
    }

    public function test_cancel_from_approved(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Approved]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->cancel('Manager voided'));
        $this->assertTrue($this->transaction->status->isCancelled());
    }

    public function test_cancel_from_processing(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Processing]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->cancel('System error'));
        $this->assertTrue($this->transaction->status->isCancelled());
    }

    public function test_cancel_from_completed(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Completed]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->cancel('Refund processed'));
        $this->assertTrue($this->transaction->status->isCancelled());
    }

    public function test_cancel_from_failed(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Failed]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->cancel('Abandoned by customer'));
        $this->assertTrue($this->transaction->status->isCancelled());
    }

    public function test_cancel_from_rejected(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Rejected]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->cancel('After rejection'));
        $this->assertTrue($this->transaction->status->isCancelled());
    }

    public function test_rejected_to_cancelled(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Rejected]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->cancel('Cleanup cancelled'));
        $this->assertTrue($this->transaction->status->isCancelled());
    }

    public function test_force_status_admin_override(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Processing]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertTrue($this->stateMachine->forceStatus(TransactionStatus::Completed, 'Admin override'));
        $this->assertTrue($this->transaction->status->isCompleted());
    }

    // =====================================================================
    // INVALID TRANSITIONS
    // =====================================================================

    public function test_cannot_approve_from_draft(): void
    {
        $this->assertFalse($this->stateMachine->canTransitionTo(TransactionStatus::Approved));
        $this->assertFalse($this->stateMachine->approve());
        $this->assertTrue($this->transaction->status->isDraft());
    }

    public function test_cannot_process_from_draft(): void
    {
        $this->assertFalse($this->stateMachine->canTransitionTo(TransactionStatus::Processing));
        $this->assertFalse($this->stateMachine->startProcessing());
        $this->assertTrue($this->transaction->status->isDraft());
    }

    public function test_cannot_complete_from_approved(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Approved]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertFalse($this->stateMachine->canTransitionTo(TransactionStatus::Completed));
        $this->assertFalse($this->stateMachine->complete());
        $this->assertTrue($this->transaction->status->isApproved());
    }

    public function test_cannot_complete_from_pending_approval(): void
    {
        $this->transaction->update(['status' => TransactionStatus::PendingApproval]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertFalse($this->stateMachine->canTransitionTo(TransactionStatus::Completed));
        $this->assertFalse($this->stateMachine->complete());
        $this->assertTrue($this->transaction->status->isPendingApproval());
    }

    public function test_cannot_retry_from_draft(): void
    {
        // Draft CAN transition to PendingApproval (via submit()), but retry() is only for Failed
        $this->assertTrue($this->stateMachine->canTransitionTo(TransactionStatus::PendingApproval));
        $this->assertFalse($this->stateMachine->retry()); // retry() should fail from Draft
        $this->assertTrue($this->transaction->status->isDraft());
    }

    public function test_cannot_finalize_from_processing(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Processing]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertFalse($this->stateMachine->canTransitionTo(TransactionStatus::Finalized));
        $this->assertFalse($this->stateMachine->finalize());
        $this->assertTrue($this->transaction->status->isProcessing());
    }

    public function test_cannot_revert_from_finalized(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Finalized]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertEmpty($this->stateMachine->getAvailableTransitions());
        $this->assertFalse($this->stateMachine->cancel('Test'));
        $this->assertTrue($this->transaction->status->isFinalized());
    }

    public function test_cannot_revert_from_cancelled(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Cancelled]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertEmpty($this->stateMachine->getAvailableTransitions());
        $this->assertFalse($this->stateMachine->submit());
        $this->assertTrue($this->transaction->status->isCancelled());
    }

    public function test_cannot_revert_from_reversed(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Reversed]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertEmpty($this->stateMachine->getAvailableTransitions());
        $this->assertFalse($this->stateMachine->complete());
        $this->assertTrue($this->transaction->status->isReversed());
    }

    // =====================================================================
    // STATE GUARDS
    // =====================================================================

    public function test_is_final_returns_true_for_final_states(): void
    {
        $finalStates = [
            TransactionStatus::Completed,
            TransactionStatus::Finalized,
            TransactionStatus::Cancelled,
            TransactionStatus::Reversed,
            TransactionStatus::Rejected,
        ];

        foreach ($finalStates as $status) {
            $this->transaction->update(['status' => $status]);
            $this->assertTrue(
                $status->isFinal(),
                "Expected {$status->value} to be final"
            );
        }
    }

    public function test_is_final_returns_false_for_non_final_states(): void
    {
        $nonFinalStates = [
            TransactionStatus::Draft,
            TransactionStatus::PendingApproval,
            TransactionStatus::Approved,
            TransactionStatus::Processing,
            TransactionStatus::Failed,
        ];

        foreach ($nonFinalStates as $status) {
            $this->assertFalse(
                $status->isFinal(),
                "Expected {$status->value} to not be final"
            );
        }
    }

    public function test_is_pending_returns_true_for_pending_states(): void
    {
        $pendingStates = [
            TransactionStatus::PendingApproval,
            TransactionStatus::Approved,
            TransactionStatus::Processing,
        ];

        foreach ($pendingStates as $status) {
            $this->assertTrue(
                $status->isPending(),
                "Expected {$status->value} to be pending"
            );
        }
    }

    public function test_is_pending_returns_false_for_non_pending_states(): void
    {
        $nonPendingStates = [
            TransactionStatus::Draft,
            TransactionStatus::Completed,
            TransactionStatus::Finalized,
            TransactionStatus::Cancelled,
            TransactionStatus::Reversed,
            TransactionStatus::Failed,
            TransactionStatus::Rejected,
        ];

        foreach ($nonPendingStates as $status) {
            $this->assertFalse(
                $status->isPending(),
                "Expected {$status->value} to not be pending"
            );
        }
    }

    // =====================================================================
    // HELPER METHODS
    // =====================================================================

    public function test_get_available_transitions_from_draft(): void
    {
        $transitions = $this->stateMachine->getAvailableTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(TransactionStatus::PendingApproval, $transitions);
        $this->assertContains(TransactionStatus::Cancelled, $transitions);
    }

    public function test_get_available_transitions_from_processing(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Processing]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $transitions = $this->stateMachine->getAvailableTransitions();

        $this->assertCount(3, $transitions);
        $this->assertContains(TransactionStatus::Completed, $transitions);
        $this->assertContains(TransactionStatus::Failed, $transitions);
        $this->assertContains(TransactionStatus::Cancelled, $transitions);
    }

    public function test_get_available_transitions_from_completed(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Completed]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $transitions = $this->stateMachine->getAvailableTransitions();

        $this->assertCount(3, $transitions);
        $this->assertContains(TransactionStatus::Finalized, $transitions);
        $this->assertContains(TransactionStatus::Reversed, $transitions);
        $this->assertContains(TransactionStatus::Cancelled, $transitions);
    }

    public function test_get_available_transitions_from_finalized_is_empty(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Finalized]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        $this->assertEmpty($this->stateMachine->getAvailableTransitions());
    }

    public function test_get_transition_history_records_changes(): void
    {
        $this->stateMachine->submit();
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->stateMachine->approve();

        $history = $this->stateMachine->getTransitionHistory();

        $this->assertCount(2, $history);
        $this->assertEquals('Draft', $history[0]['from']);
        $this->assertEquals('PendingApproval', $history[0]['to']);
        $this->assertEquals('PendingApproval', $history[1]['from']);
        $this->assertEquals('Approved', $history[1]['to']);
    }

    public function test_labels_and_colors_exist_for_all_states(): void
    {
        foreach (TransactionStatus::cases() as $status) {
            $this->assertNotEmpty($status->label(), "Missing label for {$status->value}");
            $this->assertNotEmpty($status->color(), "Missing color for {$status->value}");
        }
    }

    // =====================================================================
    // FULL LIFECYCLE TESTS
    // =====================================================================

    public function test_successful_full_lifecycle(): void
    {
        // Draft -> PendingApproval
        $this->assertTrue($this->stateMachine->submit());
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isPendingApproval());

        // PendingApproval -> Approved
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->approve());
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isApproved());

        // Approved -> Processing
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->startProcessing());
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isProcessing());

        // Processing -> Completed
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->complete());
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isCompleted());

        // Completed -> Finalized
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->finalize());
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isFinalized());

        // Finalized is terminal
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertEmpty($this->stateMachine->getAvailableTransitions());
    }

    public function test_failure_and_retry_lifecycle(): void
    {
        // Start processing
        $this->transaction->update(['status' => TransactionStatus::Processing]);
        $this->stateMachine = new TransactionStateMachine($this->transaction);

        // Processing -> Failed
        $this->assertTrue($this->stateMachine->fail('Out of stock'));
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isFailed());

        // Failed -> PendingApproval (retry)
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->retry());
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isPendingApproval());

        // Then approve and complete normally
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->approve());
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isApproved());

        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->startProcessing());
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isProcessing());

        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->complete());
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isCompleted());
    }

    public function test_rejection_lifecycle(): void
    {
        // Submit
        $this->stateMachine->submit();
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isPendingApproval());

        // Reject
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->reject('Missing KYC documents'));
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isRejected());

        // Rejected -> Cancelled (cleanup)
        $this->stateMachine = new TransactionStateMachine($this->transaction->fresh());
        $this->assertTrue($this->stateMachine->cancel('After rejection'));
        $this->transaction->refresh();
        $this->assertTrue($this->transaction->status->isCancelled());
    }
}
