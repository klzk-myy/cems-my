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
                <span class="check-time text-small">
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