@extends('emails.layouts.email')

@section('title', 'Large Transaction Requires Approval - ' . config('app.name'))
@section('header', 'Large Transaction Requires Approval')

@section('content')
<p>Dear {{ $notifiable->username }},</p>

<p>A large transaction requires your approval before it can be processed.</p>

<div class="alert-box alert-warning">
    <strong>Approval Required:</strong> This transaction exceeds the threshold and requires manager authorization.
</div>

<h2>Transaction Details</h2>

<div class="detail-row">
    <span class="detail-label">Transaction ID:</span>
    <span class="detail-value">#{{ $transaction->id }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Customer:</span>
    <span class="detail-value">{{ $customer?->full_name ?? 'Unknown' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Transaction Type:</span>
    <span class="detail-value">{{ $transactionType }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Amount:</span>
    <span class="detail-value" style="font-size: 18px; font-weight: 600; color: #1e3a8a;">
        {{ $amount }}
    </span>
</div>

<div class="detail-row">
    <span class="detail-label">Currency:</span>
    <span class="detail-value">{{ $currency }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Branch:</span>
    <span class="detail-value">{{ $branch }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Teller:</span>
    <span class="detail-value">{{ $teller?->username ?? 'Unknown' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Date:</span>
    <span class="detail-value">{{ $transaction->created_at->format('Y-m-d H:i:s') }}</span>
</div>

@if($transaction->notes)
<div class="detail-row">
    <span class="detail-label">Notes:</span>
    <span class="detail-value">{{ $transaction->notes }}</span>
</div>
@endif

<hr class="divider">

<p style="text-align: center;">
    <a href="{{ $url }}" class="btn">Review and Approve</a>
</p>

<p><strong>Approval Guidelines:</strong></p>
<ul>
    <li>Verify customer identity and transaction details</li>
    <li>Review transaction history for any red flags</li>
    <li>Ensure compliance with AML/CFT requirements</li>
    <li>Confirm source of funds documentation if required</li>
    <li>Check for any existing compliance flags</li>
</ul>

<p><strong>Note:</strong> This transaction will remain pending until approved or rejected. Please review and respond within a reasonable timeframe.</p>

<p>If you have any questions, please contact the branch manager or compliance team.</p>

<p>Best regards,<br>
{{ config('app.name') }} Transaction System</p>
@endsection
