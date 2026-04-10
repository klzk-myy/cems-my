@extends('layouts.app')

@section('title', 'Counter Management - CEMS-MY')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Counter Management</h1>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $stats['total'] }}</div>
        <div class="stat-card__label">Total Counters</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $stats['open'] }}</div>
        <div class="stat-card__label">Open Counters</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $stats['available'] }}</div>
        <div class="stat-card__label">Available Counters</div>
    </div>
</div>

<!-- Counters Table -->
<div class="card">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">All Counters</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Status</th>
                <th>Current User</th>
                <th>Session Time</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($counters as $counter)
            <tr>
                <td>{{ $counter->code }}</td>
                <td>{{ $counter->name }}</td>
                <td>
                    <span class="status-badge {{ $counter->status === 'active' ? 'status-badge--active' : 'status-badge--inactive' }}">
                        {{ $counter->status === 'active' ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>
                    @if($counter->sessions->count() > 0)
                        {{ $counter->sessions->first()->user->name }}
                    @else
                        <em class="text-gray-500">None</em>
                    @endif
                </td>
                <td>
                    @if($counter->sessions->count() > 0)
                        {{ $counter->sessions->first()->opened_at->format('H:i') }}
                    @else
                        <em class="text-gray-400">-</em>
                    @endif
                </td>
                <td>
                    @if($counter->sessions->count() > 0)
                        <a href="{{ route('counters.close.show', $counter) }}" class="btn btn--warning btn--sm">Close</a>
                        <a href="{{ route('counters.handover.show', $counter) }}" class="btn btn--primary btn--sm">Handover</a>
                    @else
                        <a href="{{ route('counters.open.show', $counter) }}" class="btn btn--success btn--sm">Open</a>
                    @endif
                    <a href="{{ route('counters.history', $counter) }}" class="btn btn--secondary btn--sm">History</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
