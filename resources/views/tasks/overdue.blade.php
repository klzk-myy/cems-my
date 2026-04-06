@extends('layouts.app')

@section('title', 'Overdue Tasks - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span>›</span>
    <span>Overdue Tasks</span>
</nav>

<div class="page-header">
    <h1>Overdue Tasks</h1>
    <p>Tasks past their due date requiring immediate attention</p>
</div>

<div class="alert alert-warning">
    <strong>{{ $tasks->total() }}</strong> overdue task(s) found. Please address these as soon as possible.
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
                <th>Days Overdue</th>
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
                <td class="text-danger">{{ $task->due_at->format('Y-m-d') }}</td>
                <td class="text-danger">{{ $task->due_at->diffInDays(now()) }} days</td>
                <td>
                    <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-primary">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem;">
                    No overdue tasks. Great job!
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
.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.alert-warning {
    background: #feebc8;
    border-left: 4px solid #dd6b20;
    color: #c05621;
}

.priority-urgent { background: #fed7d7; color: #c53030; padding: 0.25rem 0.5rem; border-radius: 4px; }
.priority-high { background: #feebc8; color: #c05621; padding: 0.25rem 0.5rem; border-radius: 4px; }
.priority-medium { background: #fefcbf; color: #975a16; padding: 0.25rem 0.5rem; border-radius: 4px; }
.priority-low { background: #c6f6d5; color: #276749; padding: 0.25rem 0.5rem; border-radius: 4px; }

.text-danger { color: #e53e3e; font-weight: 600; }
</style>
@endsection