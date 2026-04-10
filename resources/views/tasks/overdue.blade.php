@extends('layouts.app')

@section('title', 'Overdue Tasks - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span>/</span>
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
                <td colspan="7" class="text-center p-8">
                    No overdue tasks. Great job!
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
