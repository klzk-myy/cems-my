@extends('layouts.app')

@section('title', 'Tasks - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Tasks</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Task Management</h1>
        <p class="page-header__subtitle">Manage and track compliance, operational, and administrative tasks</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('tasks.create') }}" class="btn btn--primary">Create Task</a>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $tasks->total() }}</div>
        <div class="stat-card__label">Total Tasks</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $tasks->where('status', 'Pending')->count() }}</div>
        <div class="stat-card__label">Pending</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $tasks->where('status', 'InProgress')->count() }}</div>
        <div class="stat-card__label">In Progress</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $tasks->where('due_at', '<', now())->whereNotIn('status', ['Completed', 'Cancelled'])->count() }}</div>
        <div class="stat-card__label">Overdue</div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
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
                        <span class="status-badge status-badge--{{ strtolower($task->priority) }}">
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
                            <span class="{{ $task->isOverdue() ? 'text-red-600' : '' }}">
                                {{ $task->due_at->format('Y-m-d') }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <span class="status-badge status-badge--{{ strtolower($task->status) }}">
                            {{ $task->status }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('tasks.show', $task) }}" class="btn btn--primary btn--sm">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-500">
                        No tasks found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tasks->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $tasks->links() }}
    </div>
    @endif
</div>
@endsection
