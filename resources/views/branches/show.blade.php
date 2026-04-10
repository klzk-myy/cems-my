@extends('layouts.app')

@section('title', 'Branch Details - CEMS-MY')

@section('content')
<div class="branch-detail-header">
    <div>
        <h2>{{ $branch->name }}</h2>
        <span class="branch-code">{{ $branch->code }}</span>
    </div>
    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary">Edit Branch</a>
</div>

@if(session('success'))
    <div class="alert alert-success mb-4">
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
<div class="info-card mt-6">
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
<div class="info-card child-branches mt-6">
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
    <a href="{{ route('branches.index') }}" class="btn btn-secondary">Back to List</a>
    @if($branch->is_active && !$branch->is_main)
        <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to deactivate this branch?');">
                Deactivate Branch
            </button>
        </form>
    @endif
</div>
@endsection
