@extends('emails.layouts.email')

@section('title', '[CRITICAL] STR Submission Failed - ' . config('app.name'))
@section('header', '⚠ STR Submission Failed')

@section('content')
<p>Dear {{ $notifiable->username }},</p>

<div class="alert-box alert-critical">
    <strong>⚠ CRITICAL ALERT:</strong> An STR submission to Bank Negara Malaysia has failed. Immediate action is required.
</div>

<p>A Suspicious Transaction Report (STR) submission has failed and requires immediate attention to ensure compliance with BNM requirements.</p>

<h2>Failed Submission Details</h2>

<div class="detail-row">
    <span class="detail-label">STR Number:</span>
    <span class="detail-value">{{ $strReport->str_no ?? 'N/A' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Customer:</span>
    <span class="detail-value">{{ $customer?->full_name ?? 'Unknown' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Status:</span>
    <span class="detail-value">
        <span class="status-badge status-rejected">Failed</span>
    </span>
</div>

<div class="detail-row">
    <span class="detail-label">Retry Count:</span>
    <span class="detail-value">{{ $retryCount }} / {{ $maxRetries }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Filing Deadline:</span>
    <span class="detail-value">{{ $strReport->filing_deadline?->format('Y-m-d H:i:s') ?? 'Not set' }}</span>
</div>

<hr class="divider">

<h2>Error Details</h2>

<div class="alert-box alert-critical">
    <strong>Error Message:</strong><br>
    {{ $errorMessage }}
</div>

<hr class="divider">

<p style="text-align: center;">
    <a href="{{ $url }}" class="btn">Review and Retry STR</a>
</p>

<p><strong>Immediate Actions Required:</strong></p>
<ul>
    <li><strong>Review the error message above</strong> and identify the cause of failure</li>
    <li><strong>Check network connectivity</strong> to BNM systems</li>
    <li><strong>Verify API credentials</strong> and authentication tokens</li>
    <li><strong>Review the STR data</strong> for any validation errors</li>
    <li><strong>Contact IT support</strong> if the issue persists after retry</li>
</ul>

<p><strong>Compliance Impact:</strong> This STR must be submitted to BNM within the required timeframe. Delayed submission may result in regulatory violations and penalties.</p>

<p>Please address this issue immediately.</p>

<p>Best regards,<br>
{{ config('app.name') }} Compliance System</p>
@endsection
