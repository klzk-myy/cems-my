@extends('layouts.app')

@section('title', 'Risk Dashboard')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Customer Risk Dashboard</h1>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['critical_risk'] }}</div>
        <div class="stat-card__label">Critical Risk</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $summary['high_risk'] }}</div>
        <div class="stat-card__label">High Risk</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">{{ $summary['medium_risk'] }}</div>
        <div class="stat-card__label">Medium Risk</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $summary['deteriorating_trend'] }}</div>
        <div class="stat-card__label">Deteriorating</div>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Overall Score</th>
                <th>Trend</th>
                <th>Factors</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
            @php $snapshot = $customer->latestRiskSnapshot; @endphp
            <tr>
                <td>
                    <a href="{{ route('compliance.risk-dashboard.customer', $customer->id) }}" class="text-blue-600 hover:underline">
                        {{ $customer->full_name }}
                    </a>
                </td>
                <td>
                    <span class="status-badge {{ ($snapshot && $snapshot->overall_score >= 80) ? 'status-badge--danger' : (($snapshot && $snapshot->overall_score >= 60) ? 'status-badge--flagged' : 'status-badge--inactive') }}">
                        {{ $snapshot?->overall_score ?? 'N/A' }}
                    </span>
                </td>
                <td>{{ $snapshot?->trend?->label() ?? 'N/A' }}</td>
                <td>
                    @if($snapshot && isset($snapshot->factors))
                        @foreach(array_slice($snapshot->factors, 0, 2) as $factor)
                            <span class="status-badge status-badge--inactive">{{ $factor }}</span>
                        @endforeach
                    @endif
                </td>
                <td>{{ $snapshot?->snapshot_date?->format('Y-m-d') ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-gray-500 py-8">No high-risk customers found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">
        {{ $customers->links() }}
    </div>
</div>
@endsection