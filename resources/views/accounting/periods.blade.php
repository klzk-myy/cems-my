@extends('layouts.app')

@section('title', 'Accounting Periods - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item">
            <a href="{{ route('accounting.index') }}" class="breadcrumbs__link">Accounting</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Periods</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Accounting Periods</h1>
        <p class="page-header__subtitle">Manage accounting periods for financial reporting</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $periods->total() }}</div>
        <div class="stat-card__label">Total Periods</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $periods->where('is_current', true)->count() }}</div>
        <div class="stat-card__label">Current</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $periods->where('is_closed', true)->count() }}</div>
        <div class="stat-card__label">Closed</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Period List</h3>
    </div>
    <div class="card-body p-0">
        @if($periods->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Period Code</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($periods as $period)
                <tr>
                    <td><strong>{{ $period->period_code }}</strong></td>
                    <td>{{ $period->start_date->format('Y-m-d') }}</td>
                    <td>{{ $period->end_date->format('Y-m-d') }}</td>
                    <td>
                        @if($period->is_closed)
                            <span class="status-badge status-badge--inactive">Closed</span>
                        @elseif($period->is_current)
                            <span class="status-badge status-badge--active">Current</span>
                        @else
                            <span class="status-badge status-badge--pending">Open</span>
                        @endif
                    </td>
                    <td>
                        @if(!$period->is_closed)
                            <form action="{{ route('accounting.period.close', $period) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn--warning btn--sm" onclick="return confirm('Close this period? This cannot be undone.');">
                                    Close Period
                                </button>
                            </form>
                        @else
                            <span class="status-badge status-badge--inactive">Closed</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-12 text-center">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Accounting Periods</h3>
            <p class="text-gray-500">Periods are created automatically.</p>
        </div>
        @endif
    </div>
    @if($periods->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $periods->links() }}
    </div>
    @endif
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Period Management</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Info:</strong> Accounting periods are typically monthly. The current period is automatically created if it doesn't exist.
            Closing a period locks all journal entries within that period.
        </div>
    </div>
</div>
@endsection