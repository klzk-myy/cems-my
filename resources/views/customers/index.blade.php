@extends('layouts.app')

@section('title', 'Customer Management - CEMS-MY')

@section('content')
<div class="page-header">
    <h1 class="page-header__title">Customer Management</h1>
    <p class="page-header__subtitle">Manage customer profiles, KYC documents, and risk assessments</p>
    <div class="page-header__actions">
        <a href="{{ route('customers.create') }}" class="btn btn--success">+ Add New Customer</a>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="{{ route('customers.index') }}" class="card" style="margin-bottom: 1.5rem;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="search" style="display: block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 600; color: var(--color-gray-600); text-transform: uppercase;">Search by Name</label>
            <input type="text" id="search" name="search" value="{{ e(request('search')) }}" placeholder="Customer name..." class="form-input" style="margin-bottom: 0;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="risk_rating" style="display: block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 600; color: var(--color-gray-600); text-transform: uppercase;">Risk Rating</label>
            <select id="risk_rating" name="risk_rating" class="form-select" style="margin-bottom: 0;">
                <option value="">All Ratings</option>
                @foreach($riskRatings as $rating)
                    <option value="{{ $rating }}" {{ request('risk_rating') == $rating ? 'selected' : '' }}>{{ $rating }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="is_active" style="display: block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 600; color: var(--color-gray-600); text-transform: uppercase;">Status</label>
            <select id="is_active" name="is_active" class="form-select" style="margin-bottom: 0;">
                <option value="">All</option>
                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn--primary btn--sm">Filter</button>
            <a href="{{ route('customers.index') }}" class="btn btn--secondary btn--sm">Clear</a>
        </div>
    </div>
</form>

<!-- Customer List -->
<div class="card">
    <div style="font-size: 0.875rem; color: var(--color-gray-500); margin-bottom: 1rem;">
        Showing {{ $customers->count() }} of {{ $customers->total() }} customers
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>ID Type</th>
                    <th>Nationality</th>
                    <th>Risk Rating</th>
                    <th>PEP</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->id }}</td>
                    <td>
                        <strong>{{ e($customer->full_name) }}</strong>
                        @if($customer->risk_rating === 'High')
                        <span class="status-badge status-badge--flagged" style="margin-left: 0.5rem;">High Risk</span>
                        @endif
                    </td>
                    <td>{{ e($customer->id_type) }}</td>
                    <td>{{ e($customer->nationality) }}</td>
                    <td>
                        @php
                            $riskClass = match($customer->risk_rating) {
                                'Low' => 'status-badge--completed',
                                'Medium' => 'status-badge--pending',
                                'High' => 'status-badge--flagged',
                                default => 'status-badge--pending'
                            };
                        @endphp
                        <span class="status-badge {{ $riskClass }}">{{ e($customer->risk_rating) }}</span>
                    </td>
                    <td>
                        @if($customer->pep_status)
                            <span class="status-badge status-badge--flagged">PEP</span>
                        @else
                            <span class="status-badge" style="background: var(--color-gray-100); color: var(--color-gray-600);">No</span>
                        @endif
                    </td>
                    <td>
                        @if($customer->is_active ?? true)
                            <span class="status-badge status-badge--active">Active</span>
                        @else
                            <span class="status-badge status-badge--inactive">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $customer->created_at->format('Y-m-d') }}</td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn--sm btn--primary">View</a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn--sm btn--secondary">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem; color: var(--color-gray-500);">
                        No customers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem; display: flex; justify-content: center;">
        {{ $customers->links() }}
    </div>
</div>

<!-- Quick Stats -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 1.5rem;">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $customers->total() }}</div>
        <div class="stat-card__label">Total Customers</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $customers->where('risk_rating', '!=', 'High')->count() }}</div>
        <div class="stat-card__label">Low/Medium Risk</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $customers->where('risk_rating', 'High')->count() }}</div>
        <div class="stat-card__label">High Risk</div>
    </div>
</div>
@endsection
