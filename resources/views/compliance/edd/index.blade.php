@extends('layouts.app')

@section('title', 'Enhanced Due Diligence - CEMS-MY')

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
            <span class="breadcrumbs__text">EDD Records</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Enhanced Due Diligence (EDD)</h1>
        <p class="page-header__subtitle">Document source of funds and transaction purpose for high-risk customers</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('compliance.edd.create') }}" class="btn btn-primary">+ New EDD Record</a>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $records->where('risk_level', 'Critical')->count() }}</div>
        <div class="stat-card__label">Critical Risk</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $records->where('risk_level', 'High')->count() }}</div>
        <div class="stat-card__label">High Risk</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">{{ $records->whereIn('risk_level', ['Medium', 'Low'])->count() }}</div>
        <div class="stat-card__label">Medium/Low Risk</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $records->where('status', 'Approved')->count() }}</div>
        <div class="stat-card__label">Approved</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">All EDD Records</h3>
    </div>
    <div class="card-body p-0">
        @if($records->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>EDD Reference</th>
                    <th>Customer</th>
                    <th>Risk Level</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                <tr>
                    <td><strong>{{ $record->edd_reference }}</strong></td>
                    <td>{{ $record->customer->name ?? 'N/A' }}</td>
                    <td>
                        @php
                            $riskClass = match($record->risk_level) {
                                'Critical' => 'status-badge--danger',
                                'High' => 'status-badge--flagged',
                                'Medium' => 'status-badge--pending',
                                default => 'status-badge--active'
                            };
                        @endphp
                        <span class="status-badge {{ $riskClass }}">{{ $record->risk_level }}</span>
                    </td>
                    <td>
                        <span class="status-badge status-badge--{{ $record->status->color() }}">
                            {{ $record->status->label() }}
                        </span>
                    </td>
                    <td class="text-gray-500">{{ $record->created_at->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('compliance.edd.show', $record) }}" class="btn btn--primary btn--sm">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="text-center py-12">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No EDD Records</h3>
            <p class="text-gray-500 mb-6">No Enhanced Due Diligence records found.</p>
            <a href="{{ route('compliance.edd.create') }}" class="btn btn-primary">+ Create First EDD Record</a>
        </div>
        @endif
    </div>
    @if($records->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $records->links() }}
    </div>
    @endif
</div>
@endsection
