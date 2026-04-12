@extends('emails.layouts.email')

@section('title', $subject)
@section('header', 'STR Filing Deadline Approaching')

@section('content')
<p>Dear {{ $notifiable->username }},</p>

@if($severity === 'critical')
    <div class="alert-box alert-critical">
        <strong>⚠ URGENT:</strong> This STR filing is critical and requires immediate attention.
    </div>
@elseif($severity === 'warning')
    <div class="alert-box alert-warning">
        <strong>⚠ WARNING:</strong> This STR filing deadline is approaching soon.
    </div>
@else
    <div class="alert-box alert-info">
        <strong>Notice:</strong> This STR filing deadline is approaching.
    </div>
@endif

<p>A Suspicious Transaction Report (STR) filing deadline is approaching and requires your attention.</p>

<h2>STR Details</h2>

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
        <span class="status-badge status-{{ $strReport->status?->value ?? 'pending' }}">
            {{ $strReport->status?->label() ?? 'Unknown' }}
        </span>
    </span>
</div>

<div class="detail-row">
    <span class="detail-label">Filing Deadline:</span>
    <span class="detail-value">{{ $filingDeadline?->format('Y-m-d H:i:s') ?? 'Not set' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Days Remaining:</span>
    <span class="detail-value {{ $daysRemaining < 0 ? 'priority-critical' : ($daysRemaining <= 1 ? 'priority-high' : 'priority-medium') }}">
        @if($daysRemaining < 0)
            {{ abs($daysRemaining) }} days OVERDUE
        @else
            {{ $daysRemaining }} day{{ $daysRemaining !== 1 ? 's' : '' }}
        @endif
    </span>
</div>

<hr class="divider">

<p style="text-align: center;">
    <a href="{{ $url }}" class="btn">View STR Report</a>
</p>

<p><strong>Important:</strong> BNM requires STRs to be filed within 3 working days of suspicion arising. Failure to meet this deadline may result in regulatory penalties.</p>

<p><strong>Next Steps:</strong></p>
<ul>
    <li>Review the STR details and supporting documentation</li>
    <li>Complete any pending review or approval processes</li>
    <li>Submit the STR to BNM before the deadline</li>
    <li>Ensure all required fields are properly filled</li>
</ul>

<p>If you have any questions or need assistance, please contact the compliance team immediately.</p>

<p>Best regards,<br>
{{ config('app.name') }} Compliance System</p>
@endsection
