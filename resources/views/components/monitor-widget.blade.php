@php
use App\Models\SystemHealthCheck;
use App\Models\SystemAlert;

// Get current health status
$latestChecks = SystemHealthCheck::getLatestChecks();
$overallStatus = SystemHealthCheck::getOverallStatus();
$alertCounts = SystemAlert::getUnacknowledgedCounts();
$recentAlerts = SystemAlert::unacknowledged()->latest()->limit(3)->get();
@endphp

<div class="monitor-widget">
    <div class="monitor-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            System Health
        </h3>
        <span class="monitor-status monitor-status-{{ $overallStatus }}">
            {{ ucfirst($overallStatus) }}
        </span>
    </div>

    <div class="monitor-checks">
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
            <div class="monitor-check">
                <span class="check-icon check-icon-{{ $statusClass }}">{{ $statusIcon }}</span>
                <span class="check-name">{{ ucfirst(str_replace('_', ' ', $name)) }}</span>
                @if($check)
                    <span class="check-time" title="{{ $check->checked_at }}">
                        {{ $check->checked_at->diffForHumans() }}
                    </span>
                @endif
            </div>
        @endforeach
    </div>

    @if($alertCounts['total'] > 0)
        <div class="monitor-alerts">
            <div class="alerts-header">
                <h4>Active Alerts</h4>
                <a href="/system/alerts" class="alerts-link">View All ({{ $alertCounts['total'] }})</a>
            </div>
            <div class="alerts-list">
                @forelse($recentAlerts as $alert)
                    <div class="alert-item alert-item-{{ $alert->level }}">
                        <span class="alert-level">{{ ucfirst($alert->level) }}</span>
                        <span class="alert-message" title="{{ $alert->message }}">
                            {{ Str::limit($alert->message, 50) }}
                        </span>
                        <span class="alert-time">{{ $alert->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="alert-item-empty">No active alerts</div>
                @endforelse
            </div>
        </div>
    @else
        <div class="monitor-alerts-empty">
            <span class="success-icon">✓</span>
            No active alerts
        </div>
    @endif

    <div class="monitor-footer">
        <a href="/monitor/status" class="monitor-link">View Detailed Status</a>
        <span class="last-check">
            @if(isset($latestChecks['database']) && $latestChecks['database'])
                Checked {{ $latestChecks['database']->checked_at->diffForHumans() }}
            @else
                Not yet checked
            @endif
        </span>
    </div>
</div>

<style>
.monitor-widget {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 1rem;
    margin-bottom: 1rem;
}

.monitor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.monitor-header h3 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.monitor-status {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.monitor-status-ok {
    background: #c6f6d5;
    color: #22543d;
}

.monitor-status-warning {
    background: #fefcbf;
    color: #744210;
}

.monitor-status-critical {
    background: #fed7d7;
    color: #742a2a;
}

.monitor-status-unknown {
    background: #e2e8f0;
    color: #4a5568;
}

.monitor-checks {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.monitor-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background: #f7fafc;
    border-radius: 4px;
    font-size: 0.75rem;
}

.check-icon {
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 0.625rem;
    font-weight: bold;
}

.check-icon-ok {
    background: #c6f6d5;
    color: #22543d;
}

.check-icon-warning {
    background: #fefcbf;
    color: #744210;
}

.check-icon-critical {
    background: #fed7d7;
    color: #742a2a;
}

.check-icon-gray {
    background: #e2e8f0;
    color: #4a5568;
}

.check-name {
    flex: 1;
    color: #4a5568;
}

.check-time {
    color: #a0aec0;
    font-size: 0.625rem;
}

.monitor-alerts {
    border-top: 1px solid #e2e8f0;
    padding-top: 0.75rem;
}

.alerts-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.alerts-header h4 {
    font-size: 0.75rem;
    font-weight: 600;
    color: #4a5568;
    margin: 0;
}

.alerts-link {
    font-size: 0.75rem;
    color: #3182ce;
    text-decoration: none;
}

.alerts-link:hover {
    text-decoration: underline;
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.alert-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
}

.alert-item-critical {
    background: #fed7d7;
    border-left: 3px solid #c53030;
}

.alert-item-warning {
    background: #fefcbf;
    border-left: 3px solid #d69e2e;
}

.alert-item-info {
    background: #e6fffa;
    border-left: 3px solid #3182ce;
}

.alert-level {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.625rem;
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
}

.alert-item-critical .alert-level {
    background: #c53030;
    color: white;
}

.alert-item-warning .alert-level {
    background: #d69e2e;
    color: white;
}

.alert-item-info .alert-level {
    background: #3182ce;
    color: white;
}

.alert-message {
    flex: 1;
    color: #2d3748;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.alert-time {
    color: #a0aec0;
    font-size: 0.625rem;
}

.alert-item-empty {
    padding: 0.75rem;
    text-align: center;
    color: #718096;
    font-size: 0.75rem;
}

.monitor-alerts-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background: #f0fff4;
    border-radius: 4px;
    color: #22543d;
    font-size: 0.875rem;
    margin-top: 0.75rem;
}

.success-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #38a169;
    color: white;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: bold;
}

.monitor-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e2e8f0;
}

.monitor-link {
    font-size: 0.75rem;
    color: #3182ce;
    text-decoration: none;
}

.monitor-link:hover {
    text-decoration: underline;
}

.last-check {
    font-size: 0.625rem;
    color: #a0aec0;
}
</style>
