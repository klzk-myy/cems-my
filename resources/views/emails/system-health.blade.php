@extends('emails.layouts.email')

@section('title', $subject)
@section('header', 'System Health Alert')

@section('content')
<p>Dear {{ $notifiable->username }},</p>

@if($level === 'critical')
    <div class="alert-box alert-critical">
        <strong>⚠ CRITICAL:</strong> A critical system health issue has been detected.
    </div>
@elseif($level === 'warning')
    <div class="alert-box alert-warning">
        <strong>⚠ WARNING:</strong> A system health warning has been issued.
    </div>
@else
    <div class="alert-box alert-info">
        <strong>Notice:</strong> A system health notification has been generated.
    </div>
@endif

<p>A system health alert has been generated for your attention.</p>

<h2>Alert Details</h2>

<div class="detail-row">
    <span class="detail-label">Alert ID:</span>
    <span class="detail-value">#{{ $systemAlert->id }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Level:</span>
    <span class="detail-value">
        <span class="status-badge status-{{ $level }}">
            {{ $levelLabel }}
        </span>
    </span>
</div>

@if($source)
<div class="detail-row">
    <span class="detail-label">Source:</span>
    <span class="detail-value">{{ $source }}</span>
</div>
@endif

<div class="detail-row">
    <span class="detail-label">Detected At:</span>
    <span class="detail-value">{{ $createdAt?->format('Y-m-d H:i:s') ?? 'Unknown' }}</span>
</div>

<hr class="divider">

<h2>Message</h2>

<div class="alert-box alert-{{ $level }}">
    {{ $message }}
</div>

@if($metadata && is_array($metadata) && count($metadata) > 0)
<hr class="divider">

<h2>Additional Information</h2>

@foreach($metadata as $key => $value)
    @if(!in_array($key, ['email_sent', 'email_sent_at', 'email_recipients']))
        <div class="detail-row">
            <span class="detail-label">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
            <span class="detail-value">{{ is_array($value) ? json_encode($value) : $value }}</span>
        </div>
    @endif
@endforeach
@endif

<hr class="divider">

<p style="text-align: center;">
    <a href="{{ $url }}" class="btn">View Alert Details</a>
</p>

@if($level === 'critical' || $level === 'warning')
<p><strong>Recommended Actions:</strong></p>
<ul>
    <li>Review the alert details and identify the root cause</li>
    <li>Check system logs for additional context</li>
    <li>Take corrective action as needed</li>
    <li>Monitor the system for continued issues</li>
    <li>Acknowledge the alert once resolved</li>
</ul>
@endif

<p>Best regards,<br>
{{ config('app.name') }} Monitoring System</p>
@endsection
