@extends('layouts.app')

@section('title', 'Audit Log')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Audit Log</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<h2 class="mb-6">Audit Log</h2>

<div class="filter-panel">
    <h3 class="mb-4">Filters</h3>
    <form method="GET" action="{{ route('audit.index') }}">
        <div class="filter-grid">
            <div class="filter-group">
                <label>Date From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="filter-group">
                <label>Date To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="filter-group">
                <label>User</label>
                <select name="user_id">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->username }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Action</label>
                <select name="action">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                    <option value="{{ $action }}" {{ ($filters['action'] ?? '') == $action ? 'selected' : '' }}>
                        {{ $action }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Severity</label>
                <select name="severity">
                    <option value="">All Severities</option>
                    @foreach($severities as $severity)
                    <option value="{{ $severity }}" {{ ($filters['severity'] ?? '') == $severity ? 'selected' : '' }}>
                        {{ $severity }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <a href="{{ route('audit.index') }}" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="card">
    <h3>Log Entries</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Severity</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr class="log-row" onclick="toggleDetails({{ $log->id }})" data-log-id="{{ $log->id }}">
                <td>{{ $log->id }}</td>
                <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                <td>{{ $log->user->username ?? 'System' }}</td>
                <td>
                    {{ $log->action }}
                    <span class="expand-icon">▼</span>
                </td>
                <td>{{ $log->entity_type ? $log->entity_type . ' #' . $log->entity_id : 'N/A' }}</td>
                <td>
                    <span class="severity-badge severity-{{ strtolower($log->severity ?? 'info') }}">
                        {{ $log->severity ?? 'INFO' }}
                    </span>
                </td>
                <td>{{ $log->ip_address }}</td>
            </tr>
            <tr id="details-{{ $log->id }}" class="hidden">
                <td colspan="7">
                    <div class="p-4">
                        <h4>Details</h4>
                        @if($log->old_values)
                        <p><strong>Old Values:</strong></p>
                        <div class="json-view show">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</div>
                        @endif
                        @if($log->new_values)
                        <p class="mt-4"><strong>New Values:</strong></p>
                        <div class="json-view show">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</div>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-gray-500">
                    No audit log entries found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>

<div class="export-panel">
    <h3>Export Audit Log</h3>
    <form method="POST" action="{{ route('audit.export') }}" class="export-form">
        @csrf
        <div class="filter-group">
            <label>Date From</label>
            <input type="date" name="date_from" required value="{{ now()->subDays(30)->format('Y-m-d') }}">
        </div>
        <div class="filter-group">
            <label>Date To</label>
            <input type="date" name="date_to" required value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="filter-group">
            <label>Format</label>
            <select name="format" required>
                <option value="CSV">CSV</option>
                <option value="PDF">PDF</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Export</button>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function toggleDetails(logId) {
        const detailsRow = document.getElementById('details-' + logId);
        if (detailsRow.classList.contains('hidden')) {
            detailsRow.classList.remove('hidden');
        } else {
            detailsRow.classList.add('hidden');
        }
    }
</script>
@endsection