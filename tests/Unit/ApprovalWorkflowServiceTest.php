<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\ApprovalTask;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\MathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalWorkflowService $service;

    protected MathService $mathService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mathService = new MathService;
        $this->service = new ApprovalWorkflowService($this->mathService);
    }

    // ============================================
    // Threshold Tier Tests
    // ============================================

    public function test_auto_approve_threshold_below_3000(): void
    {
        // Amount < RM 3,000 should not require approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '2999.99',
        ]);

        $this->assertFalse($this->service->requiresApproval($transaction));
        $this->assertEquals('none', $this->service->getRequiredRole($transaction));
        $this->assertEquals('0.0000', $this->service->getThresholdAmount($transaction));
    }

    public function test_supervisor_threshold_3000_to_9999(): void
    {
        // Amount RM 3,000 - 9,999.99 requires supervisor approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '5000.00',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('supervisor', $this->service->getRequiredRole($transaction));
        $this->assertEquals('3000', $this->service->getThresholdAmount($transaction));
    }

    public function test_supervisor_threshold_boundary_3000(): void
    {
        // Exactly RM 3,000 requires supervisor approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '3000.00',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('supervisor', $this->service->getRequiredRole($transaction));
    }

    public function test_supervisor_threshold_boundary_9999_99(): void
    {
        // RM 9,999.99 requires supervisor approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '9999.99',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('supervisor', $this->service->getRequiredRole($transaction));
    }

    public function test_manager_threshold_10000_to_49999(): void
    {
        // Amount RM 10,000 - 49,999.99 requires manager approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '25000.00',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('manager', $this->service->getRequiredRole($transaction));
        $this->assertEquals('10000', $this->service->getThresholdAmount($transaction));
    }

    public function test_manager_threshold_boundary_10000(): void
    {
        // Exactly RM 10,000 requires manager approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '10000.00',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('manager', $this->service->getRequiredRole($transaction));
    }

    public function test_manager_threshold_boundary_49999_99(): void
    {
        // RM 49,999.99 requires manager approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '49999.99',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('manager', $this->service->getRequiredRole($transaction));
    }

    public function test_admin_threshold_50000_and_above(): void
    {
        // Amount >= RM 50,000 requires admin approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '75000.00',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('admin', $this->service->getRequiredRole($transaction));
        $this->assertEquals('50000', $this->service->getThresholdAmount($transaction));
    }

    public function test_admin_threshold_boundary_50000(): void
    {
        // Exactly RM 50,000 requires admin approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '50000.00',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('admin', $this->service->getRequiredRole($transaction));
    }

    public function test_large_amount_admin_approval(): void
    {
        // Very large amount still requires admin approval
        $transaction = Transaction::factory()->create([
            'amount_local' => '1000000.00',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('admin', $this->service->getRequiredRole($transaction));
    }

    // ============================================
    // Auto-Approve Tests
    // ============================================

    public function test_auto_approve_eligible_returns_true_for_small_amount(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_local' => '1500.00',
        ]);

        $this->assertTrue($this->service->autoApproveIfEligible($transaction));
    }

    public function test_auto_approve_eligible_returns_false_for_large_amount(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_local' => '5000.00',
        ]);

        $this->assertFalse($this->service->autoApproveIfEligible($transaction));
    }

    public function test_boundary_2999_auto_approve(): void
    {
        // RM 2,999 should auto-approve
        $transaction = Transaction::factory()->create([
            'amount_local' => '2999.00',
        ]);

        $this->assertTrue($this->service->autoApproveIfEligible($transaction));
        $this->assertFalse($this->service->requiresApproval($transaction));
    }

    // ============================================
    // Approval Task Creation Tests
    // ============================================

    public function test_create_approval_task_for_supervisor_tier(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_local' => '5000.00',
        ]);

        $task = $this->service->createApprovalTask($transaction);

        $this->assertNotNull($task);
        $this->assertInstanceOf(ApprovalTask::class, $task);
        $this->assertEquals($transaction->id, $task->transaction_id);
        $this->assertEquals('supervisor', $task->required_role);
        $this->assertEquals('3000.0000', $task->threshold_amount);
        $this->assertEquals(ApprovalTask::STATUS_PENDING, $task->status);
    }

    public function test_create_approval_task_for_manager_tier(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_local' => '25000.00',
        ]);

        $task = $this->service->createApprovalTask($transaction);

        $this->assertNotNull($task);
        $this->assertEquals('manager', $task->required_role);
        $this->assertEquals('10000.0000', $task->threshold_amount);
    }

    public function test_create_approval_task_for_admin_tier(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_local' => '75000.00',
        ]);

        $task = $this->service->createApprovalTask($transaction);

        $this->assertNotNull($task);
        $this->assertEquals('admin', $task->required_role);
        $this->assertEquals('50000.0000', $task->threshold_amount);
    }

    public function test_create_approval_task_returns_null_for_auto_approve(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_local' => '1500.00',
        ]);

        $task = $this->service->createApprovalTask($transaction);

        $this->assertNull($task);
    }

    public function test_create_approval_task_sets_expiration(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_local' => '5000.00',
        ]);

        $task = $this->service->createApprovalTask($transaction);

        $this->assertNotNull($task->expires_at);
        $this->assertTrue($task->expires_at->isFuture());
    }

    // ============================================
    // Approve Workflow Tests
    // ============================================

    public function test_manager_can_approve_supervisor_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transaction = Transaction::factory()->create(['amount_local' => '5000.00']);
        $task = ApprovalTask::factory()->supervisorRequired()->forTransaction($transaction)->create();

        $result = $this->service->approve($task, $manager);

        $this->assertTrue($result);
        $this->assertEquals(ApprovalTask::STATUS_APPROVED, $task->status);
        $this->assertEquals($manager->id, $task->approver_id);
        $this->assertNotNull($task->decided_at);
    }

    public function test_admin_can_approve_supervisor_task(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transaction = Transaction::factory()->create(['amount_local' => '5000.00']);
        $task = ApprovalTask::factory()->supervisorRequired()->forTransaction($transaction)->create();

        $result = $this->service->approve($task, $admin);

        $this->assertTrue($result);
        $this->assertEquals(ApprovalTask::STATUS_APPROVED, $task->status);
    }

    public function test_manager_can_approve_manager_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transaction = Transaction::factory()->create(['amount_local' => '25000.00']);
        $task = ApprovalTask::factory()->managerRequired()->forTransaction($transaction)->create();

        $result = $this->service->approve($task, $manager);

        $this->assertTrue($result);
        $this->assertEquals(ApprovalTask::STATUS_APPROVED, $task->status);
    }

    public function test_admin_can_approve_manager_task(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transaction = Transaction::factory()->create(['amount_local' => '25000.00']);
        $task = ApprovalTask::factory()->managerRequired()->forTransaction($transaction)->create();

        $result = $this->service->approve($task, $admin);

        $this->assertTrue($result);
        $this->assertEquals(ApprovalTask::STATUS_APPROVED, $task->status);
    }

    public function test_admin_can_approve_admin_task(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $transaction = Transaction::factory()->create(['amount_local' => '75000.00']);
        $task = ApprovalTask::factory()->adminRequired()->forTransaction($transaction)->create();

        $result = $this->service->approve($task, $admin);

        $this->assertTrue($result);
        $this->assertEquals(ApprovalTask::STATUS_APPROVED, $task->status);
    }

    public function test_teller_cannot_approve(): void
    {
        $teller = User::factory()->create(['role' => UserRole::Teller]);
        $transaction = Transaction::factory()->create(['amount_local' => '5000.00']);
        $task = ApprovalTask::factory()->supervisorRequired()->forTransaction($transaction)->create();

        $result = $this->service->approve($task, $teller);

        $this->assertFalse($result);
        $this->assertEquals(ApprovalTask::STATUS_PENDING, $task->status);
    }

    public function test_manager_cannot_approve_admin_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transaction = Transaction::factory()->create(['amount_local' => '75000.00']);
        $task = ApprovalTask::factory()->adminRequired()->forTransaction($transaction)->create();

        $result = $this->service->approve($task, $manager);

        $this->assertFalse($result);
        $this->assertEquals(ApprovalTask::STATUS_PENDING, $task->status);
    }

    public function test_approve_with_notes(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transaction = Transaction::factory()->create(['amount_local' => '5000.00']);
        $task = ApprovalTask::factory()->supervisorRequired()->forTransaction($transaction)->create();

        $result = $this->service->approve($task, $manager, 'Approved per policy');

        $this->assertTrue($result);
        $this->assertEquals('Approved per policy', $task->notes);
    }

    public function test_cannot_approve_already_approved_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $task = ApprovalTask::factory()->approved()->create();

        $result = $this->service->approve($task, $manager);

        $this->assertFalse($result);
    }

    public function test_cannot_approve_expired_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $task = ApprovalTask::factory()->stale()->pending()->create();

        $result = $this->service->approve($task, $manager);

        $this->assertFalse($result);
    }

    // ============================================
    // Reject Workflow Tests
    // ============================================

    public function test_manager_can_reject_supervisor_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transaction = Transaction::factory()->create(['amount_local' => '5000.00']);
        $task = ApprovalTask::factory()->supervisorRequired()->forTransaction($transaction)->create();

        $result = $this->service->reject($task, $manager, 'Insufficient documentation');

        $this->assertTrue($result);
        $this->assertEquals(ApprovalTask::STATUS_REJECTED, $task->status);
        $this->assertEquals($manager->id, $task->approver_id);
        $this->assertStringContainsString('Insufficient documentation', $task->notes);
    }

    public function test_reject_includes_reason_in_notes(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transaction = Transaction::factory()->create(['amount_local' => '5000.00']);
        $task = ApprovalTask::factory()->supervisorRequired()->forTransaction($transaction)->create();

        $this->service->reject($task, $manager, 'Customer verification failed');

        $this->assertStringContainsString('Rejected: Customer verification failed', $task->notes);
    }

    public function test_teller_cannot_reject(): void
    {
        $teller = User::factory()->create(['role' => UserRole::Teller]);
        $transaction = Transaction::factory()->create(['amount_local' => '5000.00']);
        $task = ApprovalTask::factory()->supervisorRequired()->forTransaction($transaction)->create();

        $result = $this->service->reject($task, $teller, 'Test reason');

        $this->assertFalse($result);
        $this->assertEquals(ApprovalTask::STATUS_PENDING, $task->status);
    }

    public function test_cannot_reject_already_rejected_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $task = ApprovalTask::factory()->rejected()->create();

        $result = $this->service->reject($task, $manager, 'Another reason');

        $this->assertFalse($result);
    }

    // ============================================
    // Expire Task Tests
    // ============================================

    public function test_expire_pending_task(): void
    {
        $task = ApprovalTask::factory()->pending()->create();

        $result = $this->service->expireTask($task);

        $this->assertTrue($result);
        $this->assertEquals(ApprovalTask::STATUS_EXPIRED, $task->status);
        $this->assertNotNull($task->decided_at);
    }

    public function test_cannot_expire_already_approved_task(): void
    {
        $task = ApprovalTask::factory()->approved()->create();

        $result = $this->service->expireTask($task);

        $this->assertFalse($result);
    }

    public function test_cannot_expire_already_rejected_task(): void
    {
        $task = ApprovalTask::factory()->rejected()->create();

        $result = $this->service->expireTask($task);

        $this->assertFalse($result);
    }

    public function test_expire_stale_tasks(): void
    {
        // Create 3 stale tasks and 1 fresh task
        ApprovalTask::factory()->stale()->pending()->count(3)->create();
        ApprovalTask::factory()->pending()->create();

        $count = $this->service->expireStaleTasks();

        $this->assertEquals(3, $count);
        $this->assertEquals(3, ApprovalTask::where('status', ApprovalTask::STATUS_EXPIRED)->count());
        $this->assertEquals(1, ApprovalTask::where('status', ApprovalTask::STATUS_PENDING)->count());
    }

    // ============================================
    // Pending Tasks for User Tests
    // ============================================

    public function test_manager_sees_supervisor_tasks(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        ApprovalTask::factory()->supervisorRequired()->pending()->count(2)->create();
        ApprovalTask::factory()->managerRequired()->pending()->count(1)->create();

        $tasks = $this->service->getPendingTasksForUser($manager);

        $this->assertCount(3, $tasks);
    }

    public function test_admin_sees_all_tasks(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ApprovalTask::factory()->supervisorRequired()->pending()->count(2)->create();
        ApprovalTask::factory()->managerRequired()->pending()->count(2)->create();
        ApprovalTask::factory()->adminRequired()->pending()->count(2)->create();

        $tasks = $this->service->getPendingTasksForUser($admin);

        $this->assertCount(6, $tasks);
    }

    public function test_teller_sees_no_tasks(): void
    {
        $teller = User::factory()->create(['role' => UserRole::Teller]);
        ApprovalTask::factory()->supervisorRequired()->pending()->count(3)->create();

        $tasks = $this->service->getPendingTasksForUser($teller);

        $this->assertCount(0, $tasks);
    }

    public function test_pending_tasks_excludes_expired(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        ApprovalTask::factory()->supervisorRequired()->pending()->create();
        ApprovalTask::factory()->supervisorRequired()->stale()->create();

        $tasks = $this->service->getPendingTasksForUser($manager);

        $this->assertCount(1, $tasks);
    }

    // ============================================
    // Transaction Approval Status Tests
    // ============================================

    public function test_get_transaction_approval_status_no_approval_needed(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_local' => '1500.00',
        ]);

        $status = $this->service->getTransactionApprovalStatus($transaction);

        $this->assertFalse($status['requires_approval']);
        $this->assertEquals('none', $status['required_role']);
        $this->assertFalse($status['has_pending_task']);
    }

    public function test_get_transaction_approval_status_with_pending_task(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_local' => '5000.00',
        ]);
        $task = ApprovalTask::factory()->supervisorRequired()->pending()->forTransaction($transaction)->create();

        $status = $this->service->getTransactionApprovalStatus($transaction);

        $this->assertTrue($status['requires_approval']);
        $this->assertEquals('supervisor', $status['required_role']);
        $this->assertTrue($status['has_pending_task']);
        $this->assertEquals($task->id, $status['task_id']);
        $this->assertEquals(ApprovalTask::STATUS_PENDING, $status['task_status']);
    }

    public function test_get_transaction_approval_status_with_approved_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $transaction = Transaction::factory()->create([
            'amount_local' => '5000.00',
        ]);
        ApprovalTask::factory()->supervisorRequired()->approved($manager)->forTransaction($transaction)->create();

        $status = $this->service->getTransactionApprovalStatus($transaction);

        $this->assertTrue($status['requires_approval']);
        $this->assertEquals('supervisor', $status['required_role']);
        $this->assertFalse($status['has_pending_task']);
        $this->assertEquals(ApprovalTask::STATUS_APPROVED, $status['task_status']);
        $this->assertEquals($manager->id, $status['approver_id']);
    }

    // ============================================
    // Edge Cases
    // ============================================

    public function test_exact_threshold_boundaries(): void
    {
        // Test exact boundary values
        $boundaries = [
            ['2999.9999', 'none'],
            ['3000.0000', 'supervisor'],
            ['9999.9999', 'supervisor'],
            ['10000.0000', 'manager'],
            ['49999.9999', 'manager'],
            ['50000.0000', 'admin'],
        ];

        foreach ($boundaries as [$amount, $expectedRole]) {
            $transaction = Transaction::factory()->create(['amount_local' => $amount]);
            $this->assertEquals(
                $expectedRole,
                $this->service->getRequiredRole($transaction),
                "Failed for amount: {$amount}"
            );
        }
    }

    public function test_precision_with_bcmath(): void
    {
        // Test with high precision amounts
        $transaction = Transaction::factory()->create([
            'amount_local' => '9999.9999',
        ]);

        $this->assertTrue($this->service->requiresApproval($transaction));
        $this->assertEquals('supervisor', $this->service->getRequiredRole($transaction));
    }

    public function test_compliance_officer_cannot_approve(): void
    {
        $complianceOfficer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $transaction = Transaction::factory()->create(['amount_local' => '5000.00']);
        $task = ApprovalTask::factory()->supervisorRequired()->forTransaction($transaction)->create();

        $result = $this->service->approve($task, $complianceOfficer);

        $this->assertFalse($result);
    }
}
