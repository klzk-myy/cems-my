<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TaskService
{
    /**
     * Get all tasks with optional filters and role-based access control.
     *
     * @param array $filters
     * @param User|null $user
     * @return LengthAwarePaginator
     */
    public function getAllTasks(array $filters, ?User $user = null): LengthAwarePaginator
    {
        $query = Task::with(['assignedTo', 'createdBy', 'relatedCustomer']);

        if ($user) {
            $this->applyRoleBasedFilter($query, $user);
        }

        $this->applyFilters($query, $filters);

        return $query->orderByRaw("FIELD(priority, 'Urgent', 'High', 'Medium', 'Low')")
            ->orderBy('due_at', 'asc')
            ->paginate(20);
    }

    /**
     * Get tasks assigned to a specific user.
     *
     * @param int $userId
     * @return LengthAwarePaginator
     */
    public function getUserTasks(int $userId): LengthAwarePaginator
    {
        return Task::with(['assignedTo', 'createdBy'])
            ->assignedTo($userId)
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])
            ->orderByRaw("FIELD(priority, 'Urgent', 'High', 'Medium', 'Low')")
            ->orderBy('due_at', 'asc')
            ->paginate(20);
    }

    /**
     * Get all overdue tasks.
     *
     * @return LengthAwarePaginator
     */
    public function getOverdueTasks(): LengthAwarePaginator
    {
        return Task::with(['assignedTo', 'createdBy'])
            ->overdue()
            ->orderBy('due_at', 'asc')
            ->paginate(20);
    }

    /**
     * Create a new task.
     *
     * @param array $data
     * @param int $createdByUserId
     * @return Task
     */
    public function createTask(array $data, int $createdByUserId): Task
    {
        $data['created_by'] = $createdByUserId;
        $data['status'] = Task::STATUS_PENDING;

        if (!isset($data['assigned_to']) && !isset($data['assigned_role'])) {
            $data['assigned_to'] = $createdByUserId;
        }

        return Task::create($data);
    }

    /**
     * Acknowledge a task.
     *
     * @param Task $task
     * @param int $userId
     * @return Task
     * @throws \InvalidArgumentException
     */
    public function acknowledgeTask(Task $task, int $userId): Task
    {
        if ($task->acknowledged_at) {
            throw new \InvalidArgumentException('Task already acknowledged');
        }

        $task->acknowledge();

        return $task->fresh();
    }

    /**
     * Complete a task.
     *
     * @param Task $task
     * @param int $userId
     * @param string|null $notes
     * @return Task
     */
    public function completeTask(Task $task, int $userId, ?string $notes = null): Task
    {
        $task->complete($notes);

        return $task->fresh();
    }

    /**
     * Cancel a task.
     *
     * @param Task $task
     * @param int $userId
     * @param string|null $reason
     * @return Task
     */
    public function cancelTask(Task $task, int $userId, ?string $reason = null): Task
    {
        $task->update([
            'status' => Task::STATUS_CANCELLED,
            'notes' => $reason ?? $task->notes,
        ]);

        return $task->fresh();
    }

    /**
     * Escalate a task.
     *
     * @param Task $task
     * @param int $userId
     * @param string|null $reason
     * @return Task
     */
    public function escalateTask(Task $task, int $userId, ?string $reason = null): Task
    {
        $task->escalate();

        return $task->fresh();
    }

    /**
     * Get task statistics for dashboard.
     *
     * @return array
     */
    public function getTaskStats(): array
    {
        return [
            'total' => Task::count(),
            'pending' => Task::pending()->count(),
            'in_progress' => Task::inProgress()->count(),
            'overdue' => Task::overdue()->count(),
            'completed_today' => Task::where('status', Task::STATUS_COMPLETED)
                ->whereDate('completed_at', today())->count(),
        ];
    }

    /**
     * Apply role-based filtering based on user role.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param User $user
     * @return void
     */
    protected function applyRoleBasedFilter($query, User $user): void
    {
        if ($user->role->isTeller()) {
            $query->where('assigned_to', $user->id);
        } elseif ($user->role->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('assigned_role', $user->role->value);
            });
        }
    }

    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }
    }
}