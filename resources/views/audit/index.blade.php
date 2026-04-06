@extends('layouts.app')

@section('title', 'Audit Log')

@section('styles')
<style>
    .filter-panel {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    .filter-group label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 0.5rem;
    }
    .filter-group input,
    .filter-group select {
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    .severity-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .severity-info {
        background: #bee3f8;
        color: #2c5282;
    }
    .severity-warning {
        background: #fefcbf;
        color: #744210;
    }
    .severity-error {
        background: #fed7d7;
        color: #c53030;
    }
    .severity-critical {
        background: #fc8181;
        color: #742a2a;
    }
    .log-row {
        cursor: pointer;
    }
    .log-row:hover {
        background: #f7fafc;
    }
    .json-view {
        background: #2d3748;
        color: #68d391;
        padding: 1rem;
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.875rem;
        white-space: pre-wrap;
        max-height: 200px;
        overflow-y: auto;
        display: none;
    }
    .json-view.show {
        display: block;
    }
    .expand-icon {
        font-size: 0.75rem;
        color: #718096;
        margin-left: 0.5rem;
    }
    .export-panel {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }
</style>
@endsection

@section('content')
<h2>Audit Log</h2>

<div class="filter-panel">
    <h3 style="margin-bottom: 1rem;">Filters</h3>
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
        <div style="margin-top: 1rem;">
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
            <tr id="details-{{ $log->id }}" style="display: none;">
                <td colspan="7">
                    <div style="padding: 1rem;">
                        <h4>Details</h4>
                        @if($log->old_values)
                        <p><strong>Old Values:</strong></p>
                        <div class="json-view show">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</div>
                        @endif
                        @if($log->new_values)
                        <p style="margin-top: 1rem;"><strong>New Values:</strong></p>
                        <div class="json-view show">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</div>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #718096;">
                    No audit log entries found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
        {{ $logs->links() }}
    </div>
</div>

<div class="export-panel">
    <h3>Export Audit Log</h3>
    <form method="POST" action="{{ route('audit.export') }}" style="display: flex; gap: 1rem; align-items: end;">
        @csrf
        <div class="filter-group" style="flex: 1;">
            <label>Date From</label>
            <input type="date" name="date_from" required value="{{ now()->subDays(30)->format('Y-m-d') }}">
        </div>
        <div class="filter-group" style="flex: 1;">
            <label>Date To</label>
            <input type="date" name="date_to" required value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="filter-group" style="flex: 1;">
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
        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = 'table-row';
        } else {
            detailsRow.style.display = 'none';
        }
    }
</script>
@endsection
