@extends('layouts.app')

@section('title', 'Compliance Portal - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Compliance</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Compliance Portal</h1>
        <p class="page-header__subtitle">Review and resolve suspicious transaction flags for AML monitoring</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $stats['open'] ?? 0 }}</div>
        <div class="stat-card__label">Open Flags</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $stats['under_review'] ?? 0 }}</div>
        <div class="stat-card__label">Under Review</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $stats['resolved_today'] ?? 0 }}</div>
        <div class="stat-card__label">Resolved Today</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $stats['high_priority'] ?? 0 }}</div>
        <div class="stat-card__label">High Priority</div>
    </div>
</div>

<!-- STR Deadline Warning -->
@if(isset($strStats) && ($strStats['overdue'] > 0 || $strStats['near_deadline'] > 0))
<div class="alert alert-warning mb-6">
    <h4 class="mb-2 font-semibold">STR Filing Deadline Warning</h4>
    @if($strStats['overdue'] > 0)
    <p class="text-red-700">
        <strong>{{ $strStats['overdue'] }} STR(s) overdue</strong> - Filing deadline (3 working days from suspicion) has passed. Immediate action required.
    </p>
    @endif
    @if($strStats['near_deadline'] > 0)
    <p class="mt-2 text-yellow-700">
        <strong>{{ $strStats['near_deadline'] }} STR(s) approaching deadline</strong> - Filing deadline within 2 days.
    </p>
    @endif
</div>
@endif

<!-- Filter Bar -->
<div class="card mb-6">
    <div class="flex gap-4 items-center p-4">
        <label class="text-sm font-semibold text-gray-600">Status:</label>
        <select onchange="window.location.href='?status='+this.value+'&flag_type={{ request('flag_type', 'all') }}'" class="form-select" style="max-width: 200px;">
            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
            <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
            <option value="Under_Review" {{ request('status') == 'Under_Review' ? 'selected' : '' }}>Under Review</option>
            <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
        </select>

        <label class="text-sm font-semibold text-gray-600">Flag Type:</label>
        <select onchange="window.location.href='?status={{ request('status', 'all') }}&flag_type='+this.value" class="form-select" style="max-width: 200px;">
            <option value="all" {{ request('flag_type') == 'all' ? 'selected' : '' }}>All Types</option>
            <option value="Velocity" {{ request('flag_type') == 'Velocity' ? 'selected' : '' }}>Velocity</option>
            <option value="Structuring" {{ request('flag_type') == 'Structuring' ? 'selected' : '' }}>Structuring</option>
            <option value="Large_Amount" {{ request('flag_type') == 'Large_Amount' ? 'selected' : '' }}>Large Amount</option>
            <option value="EDD_Required" {{ request('flag_type') == 'EDD_Required' ? 'selected' : '' }}>EDD Required</option>
            <option value="Sanction_Match" {{ request('flag_type') == 'Sanction_Match' ? 'selected' : '' }}>Sanction Match</option>
            <option value="Pep_Status" {{ request('flag_type') == 'Pep_Status' ? 'selected' : '' }}>PEP Status</option>
            <option value="High_Risk_Customer" {{ request('flag_type') == 'High_Risk_Customer' ? 'selected' : '' }}>High Risk Customer</option>
            <option value="High_Risk_Country" {{ request('flag_type') == 'High_Risk_Country' ? 'selected' : '' }}>High Risk Country</option>
            <option value="Round_Amount" {{ request('flag_type') == 'Round_Amount' ? 'selected' : '' }}>Round Amount</option>
            <option value="Profile_Deviation" {{ request('flag_type') == 'Profile_Deviation' ? 'selected' : '' }}>Profile Deviation</option>
            <option value="Manual_Review" {{ request('flag_type') == 'Manual_Review' ? 'selected' : '' }}>Manual Review</option>
        </select>

        @if(request()->has('status') || request()->has('flag_type'))
        <a href="{{ route('compliance') }}" class="btn btn-secondary">Clear Filters</a>
        @endif
    </div>
</div>

<!-- Flags Table -->
<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Flagged Transactions</h3>
    </div>
    <div class="card-body p-0">
    @if($flags->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Flag ID</th>
                    <th>Transaction</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($flags as $flag)
                <tr>
                    <td>#{{ $flag->id }}</td>
                    <td>
                        @if($flag->transaction)
                        <a href="{{ route('transactions.show', $flag->transaction) }}" class="text-blue-600 hover:underline">
                            #{{ $flag->transaction->id }}
                        </a>
                        @else
                        <span class="text-gray-500">N/A</span>
                        @endif
                    </td>
                    <td>{{ $flag->transaction?->customer?->full_name ?? 'N/A' }}</td>
                    <td>
                        @php
                        $typeClass = match($flag->flag_type->value) {
                            'Velocity' => 'status-badge--active',
                            'Structuring' => 'status-badge--flagged',
                            'Large_Amount', 'EDD_Required' => 'status-badge--pending',
                            'Sanction_Match', 'Sanctions_Hit' => 'status-badge--danger',
                            'Pep_Status' => 'status-badge--warning',
                            'High_Risk_Customer', 'High_Risk_Country' => 'status-badge--danger',
                            default => 'status-badge--inactive'
                        };
                        @endphp
                        <span class="status-badge {{ $typeClass }}">{{ $flag->flag_type->value }}</span>
                    </td>
                    <td class="max-w-xs truncate">{{ $flag->flag_reason }}</td>
                    <td>
                        @php
                        $statusClass = match($flag->status->value) {
                            'Open' => 'status-badge--danger',
                            'Under_Review' => 'status-badge--flagged',
                            'Resolved' => 'status-badge--active',
                            default => 'status-badge--danger'
                        };
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ str_replace('_', ' ', $flag->status->value) }}</span>
                    </td>
                    <td>{{ $flag->assignedTo->username ?? 'Unassigned' }}</td>
                    <td class="text-gray-500">{{ $flag->created_at->diffForHumans() }}</td>
                    <td>
                        <div class="flex gap-2">
                            @if($flag->transaction)
                            <a href="{{ route('transactions.show', $flag->transaction) }}" class="btn btn--primary btn--sm">View</a>
                            @endif
                            @if(!$flag->alert)
                            <form action="{{ route('compliance.flags.generate-str', $flag) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn--warning btn--sm">Generate STR</button>
                            </form>
                            @endif
                            @if(!$flag->status->isResolved())
                                @if(!$flag->assigned_to || $flag->assigned_to !== auth()->id())
                                <form action="{{ route('compliance.flags.assign', $flag) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn--warning btn--sm">Assign</button>
                                </form>
                                @endif
                                @if($flag->assigned_to === auth()->id())
                                <form action="{{ route('compliance.flags.resolve', $flag) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn--success btn--sm">Resolve</button>
                                </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center py-12">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Flagged Transactions</h3>
            <p class="text-gray-500">Great! Your compliance monitoring is working effectively.</p>
            @if(request()->has('status') || request()->has('flag_type'))
            <a href="{{ route('compliance') }}" class="btn btn-primary mt-4">Clear Filters</a>
            @endif
        </div>
    @endif
    </div>
    @if($flags->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $flags->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection