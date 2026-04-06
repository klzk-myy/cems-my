@extends('layouts.app')

@section('title', 'Counter Management - CEMS-MY')

@section('styles')
<style>
    .counters-header {
        margin-bottom: 1.5rem;
    }
    .counters-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }

    .counter-stat-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        text-align: center;
    }
    .counter-stat-card .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1a365d;
    }
    .counter-stat-card .stat-label {
        color: #718096;
        margin-top: 0.5rem;
        font-size: 0.875rem;
    }
    .counter-stat-card .stat-value.text-success { color: #38a169; }
    .counter-stat-card .stat-value.text-primary { color: #3182ce; }

    .counter-table {
        width: 100%;
        border-collapse: collapse;
    }
    .counter-table th {
        background: #f7fafc;
        font-weight: 600;
        color: #4a5568;
        padding: 0.75rem;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }
    .counter-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .counter-table tr:hover {
        background: #f7fafc;
    }
    .counter-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .counter-badge.active {
        background: #c6f6d5;
        color: #276749;
    }
    .counter-badge.inactive {
        background: #e2e8f0;
        color: #718096;
    }
</style>
@endsection

@section('content')
<div class="counters-header">
    <h2>Counter Management</h2>
</div>

<!-- Summary Cards -->
<div class="grid" style="margin-bottom: 1.5rem;">
    <div class="counter-stat-card">
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">Total Counters</div>
    </div>
    <div class="counter-stat-card">
        <div class="stat-value text-success">{{ $stats['open'] }}</div>
        <div class="stat-label">Open Counters</div>
    </div>
    <div class="counter-stat-card">
        <div class="stat-value text-primary">{{ $stats['available'] }}</div>
        <div class="stat-label">Available Counters</div>
    </div>
</div>

<!-- Counters Table -->
<div class="card">
    <h2>All Counters</h2>
    <table class="counter-table">
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
                    @if($counter->status === 'active')
                        <span class="counter-badge active">Active</span>
                    @else
                        <span class="counter-badge inactive">Inactive</span>
                    @endif
                </td>
                <td>
                    @if($counter->sessions->count() > 0)
                        {{ $counter->sessions->first()->user->name }}
                    @else
                        <em>None</em>
                    @endif
                </td>
                <td>
                    @if($counter->sessions->count() > 0)
                        {{ $counter->sessions->first()->opened_at->format('H:i') }}
                    @else
                        <em>-</em>
                    @endif
                </td>
                <td>
                    @if($counter->sessions->count() > 0)
                        <a href="{{ route('counters.close.show', $counter) }}" class="btn btn-warning">Close</a>
                        <a href="{{ route('counters.handover.show', $counter) }}" class="btn" style="background: #3182ce; color: white;">Handover</a>
                    @else
                        <a href="{{ route('counters.open.show', $counter) }}" class="btn btn-primary">Open</a>
                    @endif
                    <a href="{{ route('counters.history', $counter) }}" class="btn btn-secondary">History</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
