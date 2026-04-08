@extends('layouts.app')

@section('title', 'Branch Details - CEMS-MY')

@section('styles')
<style>
    .branch-detail-header {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .branch-detail-header h2 {
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    .branch-detail-header .branch-code {
        color: #718096;
        font-size: 0.875rem;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .info-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .info-card h3 {
        color: #2d3748;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .info-row {
        display: flex;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        width: 140px;
        color: #718096;
        font-size: 0.875rem;
    }
    .info-value {
        flex: 1;
        color: #2d3748;
        font-weight: 500;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    .stat-item {
        text-align: center;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 6px;
    }
    .stat-item .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a365d;
    }
    .stat-item .stat-label {
        font-size: 0.75rem;
        color: #718096;
        margin-top: 0.25rem;
    }

    .branch-type-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .type-head_office { background: #e9d8fd; color: #6b46c1; }
    .type-branch { background: #c6f6d5; color: #276749; }
    .type-sub_branch { background: #feebc8; color: #c05621; }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-active { background: #c6f6d5; color: #276749; }
    .status-inactive { background: #e2e8f0; color: #718096; }

    .main-badge {
        display: inline-block;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.625rem;
        font-weight: 700;
        background: #3182ce;
        color: white;
        text-transform: uppercase;
    }

    .child-branches h4 {
        color: #2d3748;
        margin-bottom: 0.75rem;
    }
    .child-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .child-list li {
        padding: 0.5rem;
        background: #f7fafc;
        border-radius: 4px;
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .child-list li span:first-child {
        font-weight: 500;
    }
    .child-list li span:last-child {
        font-size: 0.875rem;
        color: #718096;
    }

    .actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }
</style>
@endsection

@section('content')
<div class="branch-detail-header">
    <div>
        <h2>{{ $branch->name }}</h2>
        <span class="branch-code">{{ $branch->code }}</span>
    </div>
    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary">Edit Branch</a>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 1rem;">
        {{ session('success') }}
    </div>
@endif

<div class="detail-grid">
    <!-- Branch Information Card -->
    <div class="info-card">
        <h3>Branch Information</h3>
        <div class="info-row">
            <span class="info-label">Code</span>
            <span class="info-value">{{ $branch->code }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Name</span>
            <span class="info-value">{{ $branch->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Type</span>
            <span class="info-value">
                @php
                    $typeClass = match($branch->type) {
                        'head_office' => 'type-head_office',
                        'branch' => 'type-branch',
                        default => 'type-sub_branch'
                    };
                    $typeLabel = match($branch->type) {
                        'head_office' => 'Head Office',
                        'branch' => 'Branch',
                        default => 'Sub-Branch'
                    };
                @endphp
                <span class="branch-type-badge {{ $typeClass }}">{{ $typeLabel }}</span>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Status</span>
            <span class="info-value">
                @if($branch->is_active)
                    <span class="status-badge status-active">Active</span>
                @else
                    <span class="status-badge status-inactive">Inactive</span>
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Main Branch</span>
            <span class="info-value">
                @if($branch->is_main)
                    <span class="main-badge">Main</span>
                @else
                    No
                @endif
            </span>
        </div>
        @if($branch->parent)
            <div class="info-row">
                <span class="info-label">Parent Branch</span>
                <span class="info-value">
                    <a href="{{ route('branches.show', $branch->parent) }}">
                        {{ $branch->parent->code }} - {{ $branch->parent->name }}
                    </a>
                </span>
            </div>
        @endif
    </div>

    <!-- Contact Information Card -->
    <div class="info-card">
        <h3>Contact Information</h3>
        <div class="info-row">
            <span class="info-label">Address</span>
            <span class="info-value">{{ $branch->address ?: '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">City</span>
            <span class="info-value">{{ $branch->city ?: '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">State</span>
            <span class="info-value">{{ $branch->state ?: '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Postal Code</span>
            <span class="info-value">{{ $branch->postal_code ?: '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Country</span>
            <span class="info-value">{{ $branch->country ?: '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Phone</span>
            <span class="info-value">{{ $branch->phone ?: '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value">{{ $branch->email ?: '-' }}</span>
        </div>
    </div>
</div>

<!-- Summary Statistics Card -->
<div class="info-card" style="margin-top: 1.5rem;">
    <h3>Branch Statistics</h3>
    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-value">{{ $stats['user_count'] }}</div>
            <div class="stat-label">Users</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $stats['counter_count'] }}</div>
            <div class="stat-label">Counters</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $stats['transaction_today'] }}</div>
            <div class="stat-label">Transactions Today</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $stats['transaction_month'] }}</div>
            <div class="stat-label">Transactions This Month</div>
        </div>
    </div>
</div>

<!-- Child Branches Card -->
@if($childBranches->count() > 0)
<div class="info-card child-branches" style="margin-top: 1.5rem;">
    <h4>Child Branches</h4>
    <ul class="child-list">
        @foreach($childBranches as $child)
            <li>
                <span>{{ $child->code }} - {{ $child->name }}</span>
                @if($child->is_active)
                    <span class="status-badge status-active">Active</span>
                @else
                    <span class="status-badge status-inactive">Inactive</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endif

<div class="actions">
    <a href="{{ route('branches.index') }}" class="btn" style="background: #e2e8f0; color: #4a5568;">Back to List</a>
    @if($branch->is_active && !$branch->is_main)
        <form action="{{ route('branches.destroy', $branch) }}" method="POST" style="display: inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to deactivate this branch?');">
                Deactivate Branch
            </button>
        </form>
    @endif
</div>
@endsection