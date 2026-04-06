@extends('layouts.app')

@section('title', 'Task Details - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('tasks.index') }}">Tasks</a>
    <span>›</span>
    <span>#{{ $task->id }}</span>
</nav>

<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <h1>{{ $task->title }}</h1>
            <p>Created {{ $task->created_at->format('Y-m-d H:i') }} by {{ $task->createdBy->name ?? 'System' }}</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            @if($task->status === 'Pending')
                <form action="{{ route('tasks.acknowledge', $task) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Acknowledge</button>
                </form>
            @endif
            @if(in_array($task->status, ['Pending', 'InProgress']))
                <form action="{{ route('tasks.complete', $task) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success">Complete</button>
                </form>
                <form action="{{ route('tasks.cancel', $task) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Cancel</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-card">
        <h3>Task Details</h3>
        <table class="detail-table">
            <tr>
                <th>Category:</th>
                <td>{{ $task->category }}</td>
            </tr>
            <tr>
                <th>Priority:</th>
                <td>
                    <span class="priority-badge priority-{{ strtolower($task->priority) }}">
                        {{ $task->priority }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="status-badge status-{{ strtolower($task->status) }}">
                        {{ $task->status }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Assigned To:</th>
                <td>{{ $task->assignedTo->name ?? 'Unassigned' }}</td>
            </tr>
            @if($task->due_at)
            <tr>
                <th>Due Date:</th>
                <td class="{{ $task->isOverdue() ? 'text-danger' : '' }}">
                    {{ $task->due_at->format('Y-m-d H:i') }}
                    @if($task->isOverdue()) (Overdue) @endif
                </td>
            </tr>
            @endif
            @if($task->acknowledged_at)
            <tr>
                <th>Acknowledged:</th>
                <td>{{ $task->acknowledged_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endif
            @if($task->completed_at)
            <tr>
                <th>Completed:</th>
                <td>{{ $task->completed_at->format('Y-m-d H:i') }} by {{ $task->completedBy->name ?? 'Unknown' }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="detail-card">
        <h3>Description</h3>
        <p>{{ $task->description ?? 'No description provided.' }}</p>
        
        @if($task->notes)
        <h3 style="margin-top: 1.5rem;">Notes</h3>
        <p>{{ $task->notes }}</p>
        @endif

        @if($task->completion_notes)
        <h3 style="margin-top: 1.5rem;">Completion Notes</h3>
        <p>{{ $task->completion_notes }}</p>
        @endif
    </div>
</div>

<div class="button-group" style="margin-top: 1.5rem;">
    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Back to Tasks</a>
</div>
@endsection

@section('styles')
<style>
.detail-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}

.detail-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.detail-card h3 {
    font-size: 1rem;
    color: #2d3748;
    margin-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.5rem;
}

.detail-table {
    width: 100%;
}

.detail-table th,
.detail-table td {
    padding: 0.5rem 0;
    text-align: left;
}

.detail-table th {
    color: #718096;
    width: 40%;
}

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