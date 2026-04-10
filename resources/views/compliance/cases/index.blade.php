@extends('layouts.app')

@section('title', 'Case Management')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item">
            <a href="{{ route('compliance') }}" class="breadcrumbs__link">Compliance</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Cases</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Case Management</h1>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $summary['total_open'] }}</div>
        <div class="stat-card__label">Total Open</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['critical'] }}</div>
        <div class="stat-card__label">Critical</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $summary['high'] }}</div>
        <div class="stat-card__label">High</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['overdue'] }}</div>
        <div class="stat-card__label">Overdue</div>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Case #</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Priority</th>
                <th>SLA Deadline</th>
                <th>Alerts</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cases as $case)
            <tr>
                <td>
                    <a href="{{ route('compliance.cases.show', $case->id) }}" class="text-blue-600 hover:underline">
                        {{ $case->case_number }}
                    </a>
                </td>
                <td>{{ $case->customer?->full_name ?? 'N/A' }}</td>
                <td>{{ $case->status->label() }}</td>
                <td>
                    <span class="status-badge status-badge--{{ $case->priority->value === 'critical' ? 'danger' : ($case->priority->value === 'high' ? 'flagged' : 'inactive') }}">
                        {{ $case->priority->label() }}
                    </span>
                </td>
                <td class="{{ $case->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                    {{ $case->sla_deadline?->format('Y-m-d H:i') ?? 'N/A' }}
                </td>
                <td>{{ $case->alerts->count() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-gray-500 py-8">No open cases</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">
        {{ $cases->links() }}
    </div>
</div>
@endsection