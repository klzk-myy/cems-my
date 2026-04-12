@extends('emails.layouts.email')

@section('title', 'Transaction Flagged for Review - ' . config('app.name'))
@section('header', 'Transaction Flagged for Review')

@section('content')
<p>Dear {{ $notifiable->username }},</p>

<p>A transaction has been flagged for compliance review and requires your attention.</p>

<div class="alert-box alert-{{ $flaggedTransaction->status?->value === 'open' ? 'warning' : 'info' }}">
    <strong>Flag Status:</strong> {{ $flaggedTransaction->status?->label() ?? 'Unknown' }}
</div>

<h2>Transaction Details</h2>

<div class="detail-row">
    <span class="detail-label">Transaction ID:</span>
    <span class="detail-value">#{{ $transaction?->id ?? 'N/A' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Customer:</span>
    <span class="detail-value">{{ $customer?->full_name ?? 'Unknown' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Flag Type:</span>
    <span class="detail-value">{{ $flagType }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Reason:</span>
    <span class="detail-value">{{ $flagReason }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Flagged By:</span>
    <span class="detail-value">{{ $flaggedBy?->username ?? 'System' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Date:</span>
    <span class="detail-value">{{ $flaggedTransaction->created_at?->format('Y-m-d H:i:s') ?? 'Unknown' }}</span>
</div>

<hr class="divider">

<p style="text-align: center;">
    <a href="{{ $url }}" class="btn">Review Transaction</a>
</p>

<p><strong>Next Steps:</strong></p>
<ul>
    <li>Review the flagged transaction details</li>
    <li>Assess the risk level and determine appropriate action</li>
    <li>Update the flag status after review</li>
    @if($flaggedTransaction->customer_id)
        <li>Consider Enhanced Due Diligence if necessary</li>
    @endif
</ul>

<p>If you have any questions, please contact the compliance team.</p>

<p>Best regards,<br>
{{ config('app.name') }} System</p>
@endsection
