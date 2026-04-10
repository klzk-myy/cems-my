@extends('layouts.app')

@section('title', 'Tasks - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Tasks</span>
</nav>

<div class="page-header">
    <div class="flex justify-between items-center">
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
        <div class="summary-card-value text-danger">{{ $tasks->where('due_at', '<', now())->whereNotIn('status', ['Completed', 'Cancelled'])->count() }}</div>
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
                <td colspan="7" class="text-center p-8">
                    No tasks found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($tasks->hasPages())
    <div class="mt-4">
        {{ $tasks->links() }}
    </div>
    @endif
</div>
@endsection
