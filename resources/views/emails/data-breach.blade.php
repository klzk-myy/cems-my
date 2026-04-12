@extends('emails.layouts.email')

@section('title', '[SECURITY ALERT] Data Breach Detected - ' . config('app.name'))
@section('header', '⚠ Security Alert: Data Breach Detected')

@section('content')
<p>Dear {{ $notifiable->username }},</p>

<div class="alert-box alert-critical">
    <strong>⚠ CRITICAL SECURITY ALERT:</strong> A potential data breach has been detected in the system. Immediate action is required.
</div>

<p>A security incident has been detected that may involve unauthorized access to sensitive data. This requires immediate investigation and response.</p>

<h2>Breach Alert Details</h2>

<div class="detail-row">
    <span class="detail-label">Alert ID:</span>
    <span class="detail-value">#{{ $dataBreachAlert->id }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Alert Type:</span>
    <span class="detail-value">{{ $alertType }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Severity:</span>
    <span class="detail-value">
        <span class="status-badge status-rejected">{{ strtoupper($severity) }}</span>
    </span>
</div>

<div class="detail-row">
    <span class="detail-label">Records Affected:</span>
    <span class="detail-value">{{ number_format($recordCount) }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Triggered By:</span>
    <span class="detail-value">{{ $triggeredBy?->username ?? 'System' }}</span>
</div>

@if($ipAddress)
<div class="detail-row">
    <span class="detail-label">IP Address:</span>
    <span class="detail-value">{{ $ipAddress }}</span>
</div>
@endif

<div class="detail-row">
    <span class="detail-label">Detected At:</span>
    <span class="detail-value">{{ $createdAt?->format('Y-m-d H:i:s') ?? 'Unknown' }}</span>
</div>

<hr class="divider">

<h2>Description</h2>

<div class="alert-box alert-critical">
    {{ $description }}
</div>

<hr class="divider">

<p style="text-align: center;">
    <a href="{{ $url }}" class="btn">View Breach Alert</a>
</p>

<p><strong>Immediate Actions Required:</strong></p>
<ul>
    <li><strong>Investigate the source</strong> of the potential breach immediately</li>
    <li><strong>Contain the incident</strong> to prevent further unauthorized access</li>
    <li><strong>Review access logs</strong> and identify suspicious activity</li>
    <li><strong>Assess the scope</strong> of potentially compromised data</li>
    <li><strong>Notify relevant stakeholders</strong> as per incident response procedures</li>
    <li><strong>Document all actions</strong> taken during the investigation</li>
    <li><strong>Prepare breach notification</strong> if required by regulations</li>
</ul>

<p><strong>Regulatory Compliance:</strong> Data breaches may require notification to affected parties and regulatory authorities within specified timeframes. Please consult with the compliance team immediately.</p>

<p>This is a critical security incident. Please respond immediately.</p>

<p>Best regards,<br>
{{ config('app.name') }} Security System</p>
@endsection
