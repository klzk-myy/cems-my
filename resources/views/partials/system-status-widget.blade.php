@php
use App\Services\MonitorService;
use App\Services\AlertService;

$monitorService = app(MonitorService::class);
$alertService = app(AlertService::class);

$status = $monitorService->getStatusSummary();
$alertData = $alertService->getDashboardWidgetData();
$overallStatus = $status['overall_status'];

$statusColors = [
    'ok' => ['bg' => '#38a169', 'text' => '#ffffff', 'border' => '#2f855a'],
    'warning' => ['bg' => '#d69e2e', 'text' => '#ffffff', 'border' => '#b7791f'],
    'critical' => ['bg' => '#e53e3e', 'text' => '#ffffff', 'border' => '#c53030'],
    'unknown' => ['bg' => '#a0aec0', 'text' => '#ffffff', 'border' => '#718096'],
];

$statusIcons = [
    'ok' => '✓',
    'warning' => '⚠',
    'critical' => '✗',
    'unknown' => '?',
];

$color = $statusColors[$overallStatus] ?? $statusColors['unknown'];
$icon = $statusIcons[$overallStatus] ?? $statusIcons['unknown'];
$bgStyle = 'background-color: ' . $color['bg'] . '; color: ' . $color['text'] . '; border-color: ' . $color['border'];
@endphp

<div class="system-status-widget">
    <div class="status-header" style="{{ $bgStyle }}">
        <div class="status-icon">{{ $icon }}</div>
        <div class="status-text">
            <span class="status-label">System Status</span>
            <span class="status-value">{{ ucfirst($overallStatus) }}</span>
        </div>
    </div>

    <div class="status-details">
        @foreach($status['checks'] as $name => $check)
            @php
                $checkStatus = $check ? $check->status : 'unknown';
                $checkIcon = $statusIcons[$checkStatus] ?? $statusIcons['unknown'];
                $checkColor = '#a0aec0';
                if ($checkStatus === 'ok') {
                    $checkColor = '#38a169';
                } elseif ($checkStatus === 'warning') {
                    $checkColor = '#d69e2e';
                } elseif ($checkStatus === 'critical') {
                    $checkColor = '#e53e3e';
                }
                $checkedAt = $check ? $check->checked_at : null;
                $checkIconStyle = 'color: ' . $checkColor;
            @endphp
            <div class="check-item">
                <span class="check-icon" style="{{ $checkIconStyle }}">{{ $checkIcon }}</span>
                <span class="check-name">{{ ucfirst(str_replace('_', ' ', $name)) }}</span>
                <span class="check-time" style="color: #718096; font-size: 0.75rem;">
                    {{ $checkedAt ? $checkedAt->diffForHumans() : 'Never' }}
                </span>
            </div>
        @endforeach
    </div>

    @if($alertData['total'] > 0)
        <div class="alert-summary">
            <div class="alert-counts">
                @if($alertData['has_critical'])
                    <span class="alert-count critical">{{ $alertData['counts']['critical'] }} Critical</span>
                @endif
                @if($alertData['counts']['warning'] > 0)
                    <span class="alert-count warning">{{ $alertData['counts']['warning'] }} Warning</span>
                @endif
                @if($alertData['counts']['info'] > 0)
                    <span class="alert-count info">{{ $alertData['counts']['info'] }} Info</span>
                @endif
            </div>
        </div>
    @endif
</div>

<style>
    .system-status-widget {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        font-family: system-ui, -apple-system, sans-serif;
    }

    .status-header {
        display: flex;
        align-items: center;
        padding: 1rem;
        gap: 0.75rem;
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    .status-icon {
        font-size: 1.5rem;
        font-weight: bold;
        width: 2.5rem;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
    }

    .status-text {
        display: flex;
        flex-direction: column;
    }

    .status-label {
        font-size: 0.75rem;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-value {
        font-size: 1.25rem;
        font-weight: 600;
    }

    .status-details {
        padding: 0.75rem;
        background: #f7fafc;
    }

    .check-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0;
        font-size: 0.875rem;
    }

    .check-icon {
        width: 1.25rem;
        text-align: center;
    }

    .check-name {
        flex: 1;
        color: #4a5568;
    }

    .alert-summary {
        padding: 0.75rem;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }

    .alert-counts {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .alert-count {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .alert-count.critical {
        background: #fed7d7;
        color: #c53030;
    }

    .alert-count.warning {
        background: #fef5e7;
        color: #b7791f;
    }

    .alert-count.info {
        background: #e6f2ff;
        color: #3182ce;
    }
</style>
