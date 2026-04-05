@extends('layouts.app')

@section('title', 'My Tasks - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span>›</span>
    <span>My Tasks</span>
</nav>

<div class="page-header">
    <h1>My Tasks</h1>
    <p>Tasks assigned to you</p>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Priority</th>
                <th>Title</th>
                <th>Category</th>
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
                <td colspan="6" style="text-align: center; padding: 2rem;">
                    No tasks assigned to you.
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
.priority-urgent { background: #fed7d7; color: #c53030; padding: 0.25rem 0.5rem; border-radius: 4px; }
.priority-high { background: #feebc8; color: #c05621; padding: 0.25rem 0.5rem; border-radius: 4px; }
.priority-medium { background: #fefcbf; color: #975a16; padding: 0.25rem 0.5rem; border-radius: 4px; }
.priority-low { background: #c6f6d5; color: #276749; padding: 0.25rem 0.5rem; border-radius: 4px; }

.status-pending { background: #e2e8f0; color: #4a5568; padding: 0.25rem 0.5rem; border-radius: 4px; }
.status-inprogress { background: #bee3f8; color: #2c5282; padding: 0.25rem 0.5rem; border-radius: 4px; }
.status-completed { background: #c6f6d5; color: #276749; padding: 0.25rem 0.5rem; border-radius: 4px; }
.status-cancelled { background: #e2e8f0; color: #718096; padding: 0.25rem 0.5rem; border-radius: 4px; }

.text-danger { color: #e53e3e; font-weight: 600; }
</style>
@endsection