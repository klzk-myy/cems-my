@extends('emails.layouts.email')

@section('title', '[URGENT] Sanctions Match Detected - ' . config('app.name'))
@section('header', '⚠ Sanctions Match Detected')

@section('content')
<p>Dear {{ $notifiable->username }},</p>

<div class="alert-box alert-critical">
    <strong>⚠ URGENT COMPLIANCE ALERT:</strong> A potential sanctions match has been detected. Immediate review is required.
</div>

<p>A sanctions screening has identified a potential match that requires immediate review and action.</p>

<h2>Match Details</h2>

<div class="detail-row">
    <span class="detail-label">Match ID:</span>
    <span class="detail-value">#{{ $sanctionEntry->id }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Match Type:</span>
    <span class="detail-value">{{ $matchType }}</span>
</div>

@if($sanctionEntry->customer_id)
<div class="detail-row">
    <span class="detail-label">Customer:</span>
    <span class="detail-value">{{ $sanctionEntry->customer?->full_name ?? 'Unknown' }}</span>
</div>
@endif

@if($sanctionEntry->transaction_id)
<div class="detail-row">
    <span class="detail-label">Transaction ID:</span>
    <span class="detail-value">#{{ $sanctionEntry->transaction_id }}</span>
</div>
@endif

<div class="detail-row">
    <span class="detail-label">Screened Name:</span>
    <span class="detail-value" style="font-weight: 600;">{{ $screenedName }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Matched Name:</span>
    <span class="detail-value" style="font-weight: 600;">{{ $matchedName }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Match Score:</span>
    @php
        $scoreColor = $matchScore >= 90 ? '#dc2626' : ($matchScore >= 70 ? '#ea580c' : '#059669');
    @endphp
    <span class="detail-value" style="font-size: 16px; font-weight: 600; color: {{ $scoreColor }};">
        {{ $matchScore }}%
    </span>
</div>

<div class="detail-row">
    <span class="detail-label">Sanctions List:</span>
    <span class="detail-value">{{ $sanctionList?->name ?? 'Unknown' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Whitelist Status:</span>
    <span class="detail-value">
        @if($isWhitelisted)
            <span class="status-badge status-approved">Whitelisted</span>
        @else
            <span class="status-badge status-open">Not Whitelisted</span>
        @endif
    </span>
</div>

@if($matchReason)
<div class="detail-row">
    <span class="detail-label">Match Reason:</span>
    <span class="detail-value">{{ $matchReason }}</span>
</div>
@endif

<div class="detail-row">
    <span class="detail-label">Detected At:</span>
    <span class="detail-value">{{ $createdAt?->format('Y-m-d H:i:s') ?? 'Unknown' }}</span>
</div>

<hr class="divider">

<p style="text-align: center;">
    <a href="{{ $url }}" class="btn">Review Sanctions Match</a>
</p>

<p><strong>Immediate Actions Required:</strong></p>
<ul>
    <li><strong>Review the match details</strong> carefully to determine if it is a true positive</li>
    <li><strong>Do not proceed</strong> with the transaction until the match is resolved</li>
    <li><strong>Check supporting documentation</strong> and verify customer identity</li>
    <li><strong>Document your findings</strong> in the sanctions screening record</li>
    <li><strong>If confirmed match</strong>, follow escalation procedures immediately</li>
    <li><strong>If false positive</strong>, update whitelist status with justification</li>
</ul>

<p><strong>Compliance Note:</strong> Sanctions screening is a critical AML/CFT requirement. All matches must be reviewed and resolved before proceeding with any transactions.</p>

<p>Please review this match immediately.</p>

<p>Best regards,<br>
{{ config('app.name') }} Compliance System</p>
@endsection
