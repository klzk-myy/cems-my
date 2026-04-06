@extends('layouts.app')

@section('title', 'Audit Dashboard - CEMS-MY')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Audit Dashboard</h1>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Log Entries</h5>
                    <p class="card-text display-4">{{ $stats['total_logs'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Today's Entries</h5>
                    <p class="card-text display-4">{{ $stats['today_logs'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Critical Events</h5>
                    <p class="card-text display-4 text-danger">{{ $stats['critical_logs'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Error Events</h5>
                    <p class="card-text display-4 text-warning">{{ $stats['error_logs'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Severity Distribution -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Severity Distribution</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Severity</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($severityCounts as $severity => $count)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $severityColors[$severity] ?? 'secondary' }}">
                                        {{ $severity }}
                                    </span>
                                </td>
                                <td>{{ number_format($count) }}</td>
                                <td>{{ $stats['total_logs'] > 0 ? round(($count / $stats['total_logs']) * 100, 1) : 0 }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Actions</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topActions as $action => $count)
                            <tr>
                                <td>{{ $action }}</td>
                                <td>{{ number_format($count) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Recent Activity (Last 24 Hours)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Severity</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('H:i:s') }}</td>
                            <td>{{ $log->user?->username ?? 'System' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->entity_type ? $log->entity_type . ' #' . $log->entity_id : '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $severityColors[$log->severity ?? 'INFO'] ?? 'secondary' }}">
                                    {{ $log->severity ?? 'INFO' }}
                                </span>
                            </td>
                            <td>{{ $log->ip_address }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No activity in the last 24 hours</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Archive Management -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Log Archive Management</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Archive Statistics</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Retention Period:</span>
                            <strong>{{ $archiveStats['retention_days'] }} days</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Oldest Log:</span>
                            <strong>{{ $archiveStats['oldest_log_date'] ?? 'N/A' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Newest Log:</span>
                            <strong>{{ $archiveStats['newest_log_date'] ?? 'N/A' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Archive Files:</span>
                            <strong>{{ count($archiveStats['archive_files']) }}</strong>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Archive Files</h6>
                    @if(count($archiveStats['archive_files']) > 0)
                    <ul class="list-group list-group-flush">
                        @foreach($archiveStats['archive_files'] as $file)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $file['filename'] }}</span>
                            <span class="badge bg-primary">{{ $file['size'] }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-muted">No archive files found</p>
                    @endif
                </div>
            </div>
            <div class="mt-3">
                <a href="{{ route('audit.rotate') }}" class="btn btn-warning" onclick="return confirm('This will archive logs older than 90 days. Continue?')">
                    Rotate Logs Now
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .display-4 {
        font-size: 2.5rem;
        font-weight: 300;
    }
</style>
@endsection
