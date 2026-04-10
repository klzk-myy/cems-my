@php
use App\Models\SystemHealthCheck;
use App\Models\SystemAlert;

// Get current health status
$latestChecks = SystemHealthCheck::getLatestChecks();
$overallStatus = SystemHealthCheck::getOverallStatus();
$alertCounts = SystemAlert::getUnacknowledgedCounts();
$recentAlerts = SystemAlert::unacknowledged()->latest()->limit(3)->get();
@endphp

<div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
    <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2 m-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            System Health
        </h3>
        <span class="px-2 py-1 rounded text-xs font-semibold uppercase
            @if($overallStatus === 'ok') bg-green-100 text-green-800
            @elseif($overallStatus === 'warning') bg-yellow-100 text-yellow-800
            @elseif($overallStatus === 'critical') bg-red-100 text-red-800
            @else bg-gray-200 text-gray-600
            @endif">
            {{ ucfirst($overallStatus) }}
        </span>
    </div>

    <div class="grid grid-cols-2 gap-2 mb-4">
        @foreach($latestChecks as $name => $check)
            @php
                $status = $check ? $check->status : 'unknown';
                $statusIcon = match($status) {
                    'ok' => '✓',
                    'warning' => '⚠',
                    'critical' => '✗',
                    default => '?'
                };
                $statusClass = $status === 'unknown' ? 'gray' : $status;
            @endphp
            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded text-xs">
                <span class="w-4 h-4 flex items-center justify-center rounded-full text-xs font-bold
                    @if($statusClass === 'ok') bg-green-100 text-green-800
                    @elseif($statusClass === 'warning') bg-yellow-100 text-yellow-800
                    @elseif($statusClass === 'critical') bg-red-100 text-red-800
                    @else bg-gray-200 text-gray-600
                    @endif">
                    {{ $statusIcon }}
                </span>
                <span class="flex-1 text-gray-600">{{ ucfirst(str_replace('_', ' ', $name)) }}</span>
                @if($check)
                    <span class="text-gray-400 text-xs" title="{{ $check->checked_at }}">
                        {{ $check->checked_at->diffForHumans() }}
                    </span>
                @endif
            </div>
        @endforeach
    </div>

    @if($alertCounts['total'] > 0)
        <div class="border-t border-gray-200 pt-3">
            <div class="flex justify-between items-center mb-2">
                <h4 class="text-xs font-semibold text-gray-600 m-0">Active Alerts</h4>
                <a href="/system/alerts" class="text-xs text-blue-600 hover:underline">View All ({{ $alertCounts['total'] }})</a>
            </div>
            <div class="flex flex-col gap-2">
                @forelse($recentAlerts as $alert)
                    <div class="flex items-center gap-2 p-2 rounded text-xs
                        @if($alert->level === 'critical') bg-red-50 border-l-4 border-red-500
                        @elseif($alert->level === 'warning') bg-yellow-50 border-l-4 border-yellow-500
                        @else bg-blue-50 border-l-4 border-blue-500
                        @endif">
                        <span class="font-semibold uppercase text-xs px-1 py-0.5 rounded
                            @if($alert->level === 'critical') bg-red-600 text-white
                            @elseif($alert->level === 'warning') bg-yellow-600 text-white
                            @else bg-blue-600 text-white
                            @endif">
                            {{ ucfirst($alert->level) }}
                        </span>
                        <span class="flex-1 text-gray-700 truncate" title="{{ $alert->message }}">
                            {{ Str::limit($alert->message, 50) }}
                        </span>
                        <span class="text-gray-400 text-xs">{{ $alert->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="p-3 text-center text-gray-500 text-xs">No active alerts</div>
                @endforelse
            </div>
        </div>
    @else
        <div class="flex items-center justify-center gap-2 p-4 bg-green-50 rounded text-sm text-green-800 mt-3">
            <span class="w-5 h-5 flex items-center justify-center bg-green-600 text-white rounded-full text-xs font-bold">✓</span>
            No active alerts
        </div>
    @endif

    <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-200">
        <a href="/monitor/status" class="text-xs text-blue-600 hover:underline">View Detailed Status</a>
        <span class="text-xs text-gray-400">
            @if(isset($latestChecks['database']) && $latestChecks['database'])
                Checked {{ $latestChecks['database']->checked_at->diffForHumans() }}
            @else
                Not yet checked
            @endif
        </span>
    </div>
</div>