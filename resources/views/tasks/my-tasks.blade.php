@extends('layouts.app')

@section('title', 'My Tasks - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span>/</span>
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
                <td colspan="6" class="text-center p-8">
                    No tasks assigned to you.
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
