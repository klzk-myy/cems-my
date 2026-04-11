@extends('layouts.app')

@section('title', 'Data Breach Alerts - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Data Breach Alerts</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Data Breach Alerts</h1>
        <p class="page-header__subtitle">Monitor and manage data breach notifications</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Filter Alerts</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="flex items-center gap-4">
            <select name="is_resolved" class="form-select">
                <option value="">All Alerts</option>
                <option value="0" {{ request('is_resolved') === '0' ? 'selected' : '' }}>Unresolved</option>
                <option value="1" {{ request('is_resolved') === '1' ? 'selected' : '' }}>Resolved</option>
            </select>
            <button type="submit" class="btn btn--primary">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">All Data Breach Alerts</h3>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Records</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alerts as $alert)
                <tr>
                    <td>{{ $alert->alert_type }}</td>
                    <td>
                        <span class="status-badge status-badge--{{ strtolower($alert->severity) }}">
                            {{ $alert->severity }}
                        </span>
                    </td>
                    <td>{{ $alert->triggered_by }}</td>
                    <td>{{ $alert->ip_address }}</td>
                    <td>{{ $alert->record_count ?? '-' }}</td>
                    <td>
                        <span class="status-badge status-badge--{{ $alert->is_resolved ? 'success' : 'warning' }}">
                            {{ $alert->is_resolved ? 'Resolved' : 'Open' }}
                        </span>
                    </td>
                    <td>{{ $alert->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('data-breach-alerts.show', $alert) }}" class="btn btn--primary btn--sm">View</a>
                        @if(!$alert->is_resolved)
                        <form action="{{ route('data-breach-alerts.resolve', $alert) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn btn--success btn--sm">Resolve</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-8 text-gray-500">No alerts found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($alerts->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $alerts->links() }}
    </div>
    @endif
</div>
@endsection
