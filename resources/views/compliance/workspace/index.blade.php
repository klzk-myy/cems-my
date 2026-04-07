@extends('layouts.app')

@section('title', 'Compliance Workspace')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Compliance Workspace</h1>

    <!-- KPI Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Open Alerts</div>
            <div class="text-2xl font-bold text-blue-600">{{ $alertSummary['total'] }}</div>
            <div class="text-xs text-red-500 mt-1">{{ $alertSummary['overdue'] }} overdue</div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Open Cases</div>
            <div class="text-2xl font-bold text-purple-600">{{ $caseSummary['total_open'] }}</div>
            <div class="text-xs text-red-500 mt-1">{{ $caseSummary['overdue'] }} overdue</div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">High Risk Customers</div>
            <div class="text-2xl font-bold text-orange-600">{{ $riskSummary['high_risk'] + $riskSummary['critical_risk'] }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $riskSummary['deteriorating_trend'] }} deteriorating</div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">STR Pending</div>
            <div class="text-2xl font-bold text-red-600">{{ $strDeadlineSummary['total_pending'] }}</div>
            <div class="text-xs text-red-500 mt-1">{{ $strDeadlineSummary['overdue'] }} overdue</div>
        </div>
    </div>

    <!-- Alert Priority Breakdown -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
            <div class="text-sm text-red-600">Critical</div>
            <div class="text-xl font-bold text-red-700">{{ $alertSummary['critical'] }}</div>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
            <div class="text-sm text-orange-600">High</div>
            <div class="text-xl font-bold text-orange-700">{{ $alertSummary['high'] }}</div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
            <div class="text-sm text-yellow-600">Medium</div>
            <div class="text-xl font-bold text-yellow-700">{{ $alertSummary['medium'] }}</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
            <div class="text-sm text-green-600">Low</div>
            <div class="text-xl font-bold text-green-700">{{ $alertSummary['low'] }}</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Alert Queue Panel -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-semibold">Alert Queue</h2>
                <a href="{{ route('compliance.alerts') }}" class="text-blue-600 text-sm hover:underline">View All</a>
            </div>
            <div class="p-4">
                <p class="text-gray-500 text-sm">Unassigned alerts requiring review</p>
                <div class="mt-3 text-3xl font-bold text-gray-700">{{ $alertSummary['unassigned'] }}</div>
                <p class="text-sm text-gray-400">unassigned</p>
            </div>
        </div>

        <!-- Case Management Panel -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-semibold">Case Management</h2>
                <a href="{{ route('compliance.cases') }}" class="text-blue-600 text-sm hover:underline">View All</a>
            </div>
            <div class="p-4">
                <p class="text-gray-500 text-sm">Active cases by status</p>
                <div class="mt-2 flex gap-4">
                    <div>
                        <div class="text-xl font-bold text-purple-600">{{ $caseSummary['pending_review'] }}</div>
                        <p class="text-xs text-gray-400">Pending Review</p>
                    </div>
                    <div>
                        <div class="text-xl font-bold text-blue-600">{{ $caseSummary['high'] + $caseSummary['critical'] }}</div>
                        <p class="text-xs text-gray-400">High/Critical</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Dashboard and Reporting -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Risk Dashboard Preview -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-semibold">Risk Dashboard</h2>
                <a href="{{ route('compliance.risk-dashboard') }}" class="text-blue-600 text-sm hover:underline">View All</a>
            </div>
            <div class="p-4">
                <p class="text-gray-500 text-sm">Customer risk overview</p>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <div class="text-center p-2 bg-red-50 rounded">
                        <div class="text-lg font-bold text-red-700">{{ $riskSummary['critical_risk'] }}</div>
                        <div class="text-xs text-red-600">Critical</div>
                    </div>
                    <div class="text-center p-2 bg-orange-50 rounded">
                        <div class="text-lg font-bold text-orange-700">{{ $riskSummary['high_risk'] }}</div>
                        <div class="text-xs text-orange-600">High</div>
                    </div>
                    <div class="text-center p-2 bg-gray-100 rounded">
                        <div class="text-lg font-bold text-gray-700">{{ $riskSummary['medium_risk'] }}</div>
                        <div class="text-xs text-gray-600">Medium</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reporting Panel -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-semibold">Reporting</h2>
                <a href="{{ route('compliance.reporting') }}" class="text-blue-600 text-sm hover:underline">View All</a>
            </div>
            <div class="p-4">
                <p class="text-gray-500 text-sm">Report generation and schedules</p>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <div class="text-center p-2 bg-blue-50 rounded">
                        <div class="text-lg font-bold text-blue-700">{{ $reportSummary['total_runs'] }}</div>
                        <div class="text-xs text-blue-600">Total Runs</div>
                    </div>
                    <div class="text-center p-2 bg-green-50 rounded">
                        <div class="text-lg font-bold text-green-700">{{ $reportSummary['success_rate'] }}%</div>
                        <div class="text-xs text-green-600">Success Rate</div>
                    </div>
                    <div class="text-center p-2 bg-gray-100 rounded">
                        <div class="text-lg font-bold text-gray-700">{{ $reportSummary['scheduled_runs'] }}</div>
                        <div class="text-xs text-gray-600">Scheduled</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Metrics -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Compliance KPIs (Last 30 Days)</h2>
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

    <!-- Upcoming Deadlines Calendar -->
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
                                    <span class="text-red-500">{{ $deadline['urgency'] }}</span>
                                @elseif($deadline['urgency'] === 'warning')
                                    <span class="text-yellow-600">{{ $deadline['urgency'] }}</span>
                                @else
                                    <span class="text-green-600">{{ $deadline['urgency'] }}</span>
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