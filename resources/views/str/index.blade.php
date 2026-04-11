@extends('layouts.app')

@section('title', 'STR Reports - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">STR Reports</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Suspicious Transaction Reports (STR)</h1>
        <p class="page-header__subtitle">Manage STR filings for BNM compliance - Must be filed within 24 hours of suspicion</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $stats['draft'] ?? 0 }}</div>
        <div class="stat-card__label">Drafts</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $stats['pending_review'] ?? 0 }}</div>
        <div class="stat-card__label">Pending Review</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $stats['pending_approval'] ?? 0 }}</div>
        <div class="stat-card__label">Pending Approval</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $stats['submitted'] ?? 0 }}</div>
        <div class="stat-card__label">Submitted</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $stats['acknowledged'] ?? 0 }}</div>
        <div class="stat-card__label">Acknowledged</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex items-center gap-4">
            <label class="font-semibold">Status:</label>
            <select onchange="window.location.href='?status='+this.value" class="form-select" style="width: auto;">
                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="pending_review" {{ request('status') == 'pending_review' ? 'selected' : '' }}>Pending Review</option>
                <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="acknowledged" {{ request('status') == 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
            </select>
            @if(request('status'))
                <a href="{{ route('str.index') }}" class="btn btn--secondary btn--sm">Clear Filter</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">All STR Reports</h3>
        <a href="{{ route('str.create') }}" class="btn btn--danger btn--sm">+ Create STR</a>
    </div>
    <div class="card-body p-0">
        @if($strReports->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>STR No.</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Filed By</th>
                    <th>Created</th>
                    <th>Submitted</th>
                    <th>BNM Reference</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($strReports as $str)
                <tr>
                    <td><strong>{{ $str->str_no }}</strong></td>
                    <td>{{ $str->customer->full_name ?? 'N/A' }}</td>
                    <td>
                        <span class="status-badge status-badge--{{ strtolower($str->status->value) }}">
                            {{ $str->status->label() }}
                        </span>
                    </td>
                    <td>{{ $str->creator->full_name ?? 'N/A' }}</td>
                    <td>{{ $str->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $str->submitted_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ $str->bnm_reference ?? '-' }}</td>
                    <td>
                        <a href="{{ route('str.show', $str) }}" class="btn btn--primary btn--sm">View</a>
                        @if($str->isDraft())
                            <form action="{{ route('str.submit-review', $str) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn--primary btn--sm">Submit for Review</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="p-4 border-t border-gray-200 flex justify-end">
            {{ $strReports->appends(request()->query())->links() }}
        </div>
        @else
        <div class="card-body text-center">
            <p class="text-gray-500">No suspicious transaction reports have been filed yet.</p>
        </div>
        @endif
    </div>
</div>
@endsection