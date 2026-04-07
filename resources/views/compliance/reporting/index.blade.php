@extends('layouts.app')

@section('title', 'Compliance Reporting')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Compliance Reporting</h1>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-blue-600">Total Runs</div>
            <div class="text-2xl font-bold text-blue-700">{{ $summary['total_runs'] }}</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-green-600">Success Rate</div>
            <div class="text-2xl font-bold text-green-700">{{ $summary['success_rate'] }}%</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm text-red-600">Failed</div>
            <div class="text-2xl font-bold text-red-700">{{ $summary['failed_runs'] }}</div>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="text-sm text-purple-600">Scheduled</div>
            <div class="text-2xl font-bold text-purple-700">{{ $summary['scheduled_runs'] }}</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-3">Generate Report</h3>
            <a href="{{ route('compliance.reporting.generate') }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Generate New Report
            </a>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-3">Report Schedules</h3>
            <a href="{{ route('compliance.reporting.schedule') }}" class="inline-block bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                Manage Schedules
            </a>
        </div>
    </div>

    <!-- KPI Metrics -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Key Performance Indicators (Last 30 Days)</h2>
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
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-semibold">Upcoming Deadlines</h2>
            <a href="{{ route('compliance.reporting.deadlines') }}" class="text-blue-600 text-sm hover:underline">View Calendar</a>
        </div>
        <div class="p-4">
            @if(empty($deadlines))
                <p class="text-gray-500 text-sm">No upcoming deadlines</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="pb-2">Type</th>
                            <th class="pb-2">Deadline</th>
                            <th class="pb-2">Urgency</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($deadlines, 0, 5) as $deadline)
                        <tr class="border-b">
                            <td class="py-2">
                                @if($deadline['type'] === 'str')
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">STR</span>
                                @else
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">{{ strtoupper($deadline['report_type'] ?? 'Report') }}</span>
                                @endif
                            </td>
                            <td class="py-2">{{ $deadline['deadline'] }}</td>
                            <td class="py-2">
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
</div>
@endsection