@extends('layouts.app')

@section('title', 'Customer Management - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Customers</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-header__title">Customer Management</h1>
    <p class="page-header__subtitle">Manage customer profiles, KYC documents, and risk assessments</p>
    <div class="page-header__actions">
        <a href="{{ route('customers.create') }}" class="btn btn--success">+ Add New Customer</a>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="{{ route('customers.index') }}" class="card mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
        <div class="mb-0">
            <label for="search" class="block text-xs font-semibold text-gray-600 uppercase mb-1.5">Search by Name</label>
            <input type="text" id="search" name="search" value="{{ e(request('search')) }}" placeholder="Customer name..."
                   class="form-input mb-0">
        </div>
        <div class="mb-0">
            <label for="risk_rating" class="block text-xs font-semibold text-gray-600 uppercase mb-1.5">Risk Rating</label>
            <select id="risk_rating" name="risk_rating" class="form-select mb-0">
                <option value="">All Ratings</option>
                @foreach($riskRatings as $rating)
                    <option value="{{ $rating }}" {{ request('risk_rating') == $rating ? 'selected' : '' }}>{{ $rating }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-0">
            <label for="is_active" class="block text-xs font-semibold text-gray-600 uppercase mb-1.5">Status</label>
            <select id="is_active" name="is_active" class="form-select mb-0">
                <option value="">All</option>
                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn btn--primary btn--sm">Filter</button>
            <a href="{{ route('customers.index') }}" class="btn btn--secondary btn--sm">Clear</a>
        </div>
    </div>
</form>

<!-- Customer List -->
<div class="card">
    <div class="text-sm text-gray-500 mb-4">
        Showing {{ $customers->count() }} of {{ $customers->total() }} customers
    </div>

    <div class="overflow-x-auto">
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
                    <td class="font-mono text-xs">{{ $customer->id }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ e($customer->full_name) }}</span>
                            @if($customer->risk_rating === 'High')
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                High Risk
                            </span>
                            @endif
                        </div>
                    </td>
                    <td class="text-sm">{{ e($customer->id_type) }}</td>
                    <td class="text-sm">{{ e($customer->nationality) }}</td>
                    <td>
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ match($customer->risk_rating) {
                            'Low' => 'bg-green-100 text-green-800',
                            'Medium' => 'bg-yellow-100 text-yellow-800',
                            'High' => 'bg-orange-100 text-orange-800',
                            default => 'bg-gray-100 text-gray-600'
                        } }}">
                            {{ e($customer->risk_rating) }}
                        </span>
                    </td>
                    <td>
                        @if($customer->pep_status)
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">PEP</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">No</span>
                        @endif
                    </td>
                    <td>
                        @if($customer->is_active ?? true)
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-500">{{ $customer->created_at->format('Y-m-d') }}</td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn--primary btn--sm">View</a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn--secondary btn--sm">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-12 text-gray-500">
                        No customers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-center">
        {{ $customers->links() }}
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-6">
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
