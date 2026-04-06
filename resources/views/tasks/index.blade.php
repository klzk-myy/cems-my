@extends('layouts.app')

@section('title', 'Tasks - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span>›</span>
    <span>Tasks</span>
</nav>

<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>Task Management</h1>
            <p>Manage and track compliance, operational, and administrative tasks</p>
        </div>
        <a href="{{ route('tasks.create') }}" class="btn btn-primary">Create Task</a>
    </div>
</div>

<div class="summary-grid">
    <div class="summary-card">
        <div class="summary-card-label">Total Tasks</div>
        <div class="summary-card-value">{{ $tasks->total() }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Pending</div>
        <div class="summary-card-value">{{ $tasks->where('status', 'Pending')->count() }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">In Progress</div>
        <div class="summary-card-value">{{ $tasks->where('status', 'InProgress')->count() }}</div>
    </div>
    <div class="summary-card">
        <div class="summary-card-label">Overdue</div>
        <div class="summary-card-value" style="color: #e53e3e;">{{ $tasks->where('due_at', '<', now())->whereNotIn('status', ['Completed', 'Cancelled'])->count() }}</div>
    </div>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Priority</th>
                <th>Title</th>
                <th>Category</th>
                <th>Assigned To</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $task)
            <tr>
                <td>
                    <span class="priority-badge priority-{{ strtolower($task->priority) }}">
                        {{ $task->priority }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>
                </td>
                <td>{{ $task->category }}</td>
                <td>{{ $task->assignedTo->name ?? 'Unassigned' }}</td>
                <td>
                    @if($task->due_at)
                        <span class="{{ $task->isOverdue() ? 'text-danger' : '' }}">
                            {{ $task->due_at->format('Y-m-d') }}
                        </span>
                    @else
                        -
                    @endif
                </td>
                <td>
                    <span class="status-badge status-{{ strtolower($task->status) }}">
                        {{ $task->status }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-primary">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem;">
                    No tasks found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($tasks->hasPages())
    <div style="margin-top: 1rem;">
        {{ $tasks->links() }}
    </div>
    @endif
</div>
@endsection

@section('styles')
<style>
.priority-urgent { background: #fed7d7; color: #c53030; }
.priority-high { background: #feebc8; color: #c05621; }
.priority-medium { background: #fefcbf; color: #975a16; }
.priority-low { background: #c6f6d5; color: #276749; }

.status-pending { background: #e2e8f0; color: #4a5568; }
.status-inprogress { background: #bee3f8; color: #2c5282; }
.status-completed { background: #c6f6d5; color: #276749; }
.status-cancelled { background: #e2e8f0; color: #718096; }

.text-danger { color: #e53e3e; font-weight: 600; }
</style>
@endsection