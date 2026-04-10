@extends('layouts.app')

@section('title', 'Alert Triage')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Alert Triage</h1>
    </div>
    <div class="page-header__actions">
        <span class="text-sm text-gray-500">{{ $summary['unassigned'] }} unassigned</span>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['critical'] }}</div>
        <div class="stat-card__label">Critical</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $summary['high'] }}</div>
        <div class="stat-card__label">High</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">{{ $summary['medium'] }}</div>
        <div class="stat-card__label">Medium</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $summary['low'] }}</div>
        <div class="stat-card__label">Low</div>
    </div>
</div>

<div class="card">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Unassigned Alerts</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Priority</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Risk Score</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alerts as $alert)
            <tr>
                <td>
                    <span class="status-badge status-badge--{{ $alert->priority->value === 'critical' ? 'danger' : ($alert->priority->value === 'high' ? 'flagged' : ($alert->priority->value === 'medium' ? 'pending' : 'active')) }}">
                        {{ $alert->priority->label() }}
                    </span>
                </td>
                <td>{{ $alert->customer?->full_name ?? 'N/A' }}</td>
                <td>{{ $alert->type?->value ?? 'N/A' }}</td>
                <td>{{ $alert->risk_score }}</td>
                <td>{{ $alert->created_at->diffForHumans() }}</td>
                <td>
                    <a href="{{ route('compliance.alerts.show', $alert->id) }}" class="btn btn--primary btn--sm">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-gray-500 py-8">No unassigned alerts</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">
        {{ $alerts->links() }}
    </div>
</div>
@endsection