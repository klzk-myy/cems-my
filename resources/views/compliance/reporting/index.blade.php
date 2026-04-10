@extends('layouts.app')

@section('title', 'Compliance Reporting')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Compliance Reporting</h1>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $summary['total_runs'] }}</div>
        <div class="stat-card__label">Total Runs</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $summary['success_rate'] }}%</div>
        <div class="stat-card__label">Success Rate</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['failed_runs'] }}</div>
        <div class="stat-card__label">Failed</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $summary['scheduled_runs'] }}</div>
        <div class="stat-card__label">Scheduled</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="card">
        <div class="p-4">
            <h3 class="font-semibold mb-3">Generate Report</h3>
            <a href="{{ route('compliance.reporting.generate') }}" class="btn btn--primary">Generate New Report</a>
        </div>
    </div>
    <div class="card">
        <div class="p-4">
            <h3 class="font-semibold mb-3">Report Schedules</h3>
            <a href="{{ route('compliance.reporting.schedule') }}" class="btn btn--secondary">Manage Schedules</a>
        </div>
    </div>
</div>

<!-- KPI Metrics -->
<div class="card mb-6">
    <div class="p-4 border-b">
        <h3 class="text-lg font-semibold">Key Performance Indicators (Last 30 Days)</h3>
    </div>
    <div class="p-4 grid grid-cols-4 gap-4">
        <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $kpis['flag_resolution_avg_hours'] }}h</div>
            <div class="text-sm text-gray-500">Avg Flag Resolution</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ $kpis['str_on_time_percent'] }}%</div>
            <div class="text-sm text-gray-500">STR On-Time</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $kpis['edd_completion_rate_percent'] }}%</div>
            <div class="text-sm text-gray-500">EDD Completion</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-orange-600">{{ $kpis['reports_on_schedule_percent'] }}%</div>
            <div class="text-sm text-gray-500">Reports On Schedule</div>
        </div>
    </div>
</div>

<!-- Upcoming Deadlines -->
<div class="card">
    <div class="p-4 border-b flex justify-between items-center">
        <h3 class="text-lg font-semibold">Upcoming Deadlines</h3>
        <a href="{{ route('compliance.reporting.deadlines') }}" class="text-blue-600 text-sm hover:underline">View Calendar</a>
    </div>
    <div class="p-4">
        @if(empty($deadlines))
            <p class="text-gray-500 text-sm">No upcoming deadlines</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Deadline</th>
                        <th>Urgency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($deadlines, 0, 5) as $deadline)
                    <tr>
                        <td>
                            @if($deadline['type'] === 'str')
                                <span class="status-badge status-badge--danger">STR</span>
                            @else
                                <span class="status-badge status-badge--active">{{ strtoupper($deadline['report_type'] ?? 'Report') }}</span>
                            @endif
                        </td>
                        <td>{{ $deadline['deadline'] }}</td>
                        <td>
                            @if($deadline['urgency'] === 'overdue')
                                <span class="text-red-600 font-medium">Overdue</span>
                            @elseif($deadline['urgency'] === 'critical')
                                <span class="text-red-500">Critical</span>
                            @elseif($deadline['urgency'] === 'warning')
                                <span class="text-yellow-600">Warning</span>
                            @else
                                <span class="text-green-600">Normal</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection