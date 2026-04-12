@extends('emails.layouts.email')

@section('title', 'Compliance Case Assigned - ' . config('app.name'))
@section('header', 'Compliance Case Assigned')

@section('content')
<p>Dear {{ $notifiable->username }},</p>

<p>A compliance case has been assigned to you and requires your attention.</p>

<div class="alert-box alert-{{ $complianceCase->priority?->value === 'critical' ? 'critical' : ($complianceCase->priority?->value === 'high' ? 'warning' : 'info') }}">
    <strong>Priority:</strong> 
    <span class="priority-{{ $complianceCase->priority?->value ?? 'low' }}">
        {{ $complianceCase->priority?->label() ?? 'Unknown' }}
    </span>
</div>

<h2>Case Details</h2>

<div class="detail-row">
    <span class="detail-label">Case Number:</span>
    <span class="detail-value">{{ $complianceCase->case_number }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Case Type:</span>
    <span class="detail-value">{{ $caseType }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Customer:</span>
    <span class="detail-value">{{ $customer?->full_name ?? 'Unknown' }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Severity:</span>
    <span class="detail-value">{{ $severity }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Status:</span>
    <span class="detail-value">
        <span class="status-badge status-{{ $complianceCase->status?->value ?? 'open' }}">
            {{ $complianceCase->status?->label() ?? 'Unknown' }}
        </span>
    </span>
</div>

@if($slaDeadline)
<div class="detail-row">
    <span class="detail-label">SLA Deadline:</span>
    <span class="detail-value">{{ $slaDeadline->format('Y-m-d H:i:s') }}</span>
</div>

<div class="detail-row">
    <span class="detail-label">Time Remaining:</span>
    <span class="detail-value">
        @if($daysUntilDeadline !== null)
            @if($daysUntilDeadline < 0)
                <span class="priority-critical">{{ abs($daysUntilDeadline) }} days overdue</span>
            @elseif($daysUntilDeadline <= 1)
                <span class="priority-high">{{ $daysUntilDeadline }} day remaining</span>
            @else
                {{ $daysUntilDeadline }} days remaining
            @endif
        @else
            Not set
        @endif
    </span>
</div>
@endif

<div class="detail-row">
    <span class="detail-label">Assigned By:</span>
    <span class="detail-value">{{ $assignedBy?->username ?? 'System' }}</span>
</div>

@if($complianceCase->case_summary)
<div class="detail-row">
    <span class="detail-label">Summary:</span>
    <span class="detail-value">{{ $complianceCase->case_summary }}</span>
</div>
@endif

<hr class="divider">

<p style="text-align: center;">
    <a href="{{ $url }}" class="btn">View Case</a>
</p>

<p><strong>Next Steps:</strong></p>
<ul>
    <li>Review the case details and supporting documentation</li>
    <li>Assess the severity and priority</li>
    <li>Begin investigation as per compliance procedures</li>
    <li>Update case notes regularly</li>
    <li>Meet SLA requirements for case resolution</li>
</ul>

<p>If you have any questions or need assistance with this case, please contact your supervisor.</p>

<p>Best regards,<br>
{{ config('app.name') }} Compliance System</p>
@endsection
