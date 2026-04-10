@extends('layouts.app')

@section('title', 'Task Details - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('tasks.index') }}">Tasks</a>
    <span>/</span>
    <span>#{{ $task->id }}</span>
</nav>

<div class="page-header">
    <div class="flex justify-between items-start">
        <div>
            <h1>{{ $task->title }}</h1>
            <p>Created {{ $task->created_at->format('Y-m-d H:i') }} by {{ $task->createdBy->name ?? 'System' }}</p>
        </div>
        <div class="flex gap-2">
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
        <h3 class="mt-6">Notes</h3>
        <p>{{ $task->notes }}</p>
        @endif

        @if($task->completion_notes)
        <h3 class="mt-6">Completion Notes</h3>
        <p>{{ $task->completion_notes }}</p>
        @endif
    </div>
</div>

<div class="btn-group mt-6">
    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Back to Tasks</a>
</div>
@endsection
