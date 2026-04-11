@extends('layouts.app')

@section('title', 'Stock Transfers - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Stock Transfers</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-header__title">Stock Transfers</h1>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Stock Transfers</span>
        @can('create', App\Models\StockTransfer::class)
        <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">New Transfer</a>
        @endcan
    </div>
    <div class="card-body">
        <form method="GET" class="flex gap-4 mb-6">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="Requested" {{ request('status') == 'Requested' ? 'selected' : '' }}>Requested</option>
                <option value="BranchManagerApproved" {{ request('status') == 'BranchManagerApproved' ? 'selected' : '' }}>BM Approved</option>
                <option value="HQApproved" {{ request('status') == 'HQApproved' ? 'selected' : '' }}>HQ Approved</option>
                <option value="InTransit" {{ request('status') == 'InTransit' ? 'selected' : '' }}>In Transit</option>
                <option value="PartiallyReceived" {{ request('status') == 'PartiallyReceived' ? 'selected' : '' }}>Partially Received</option>
                <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <input type="text" name="source_branch" placeholder="Source Branch" class="form-input" value="{{ request('source_branch') }}">
            <input type="text" name="destination_branch" placeholder="Destination Branch" class="form-input" value="{{ request('destination_branch') }}">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Transfer #</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Total (MYR)</th>
                    <th>Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $transfer)
                <tr>
                    <td><a href="{{ route('stock-transfers.show', $transfer) }}">{{ $transfer->transfer_number }}</a></td>
                    <td>{{ $transfer->type }}</td>
                    <td><span class="badge badge-{{ strtolower($transfer->status) }}">{{ $transfer->status }}</span></td>
                    <td>{{ $transfer->source_branch_name }}</td>
                    <td>{{ $transfer->destination_branch_name }}</td>
                    <td>{{ number_format($transfer->total_value_myr, 2) }}</td>
                    <td>{{ $transfer->requested_at?->format('Y-m-d') }}</td>
                    <td><a href="{{ route('stock-transfers.show', $transfer) }}" class="btn btn-sm btn-info">View</a></td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center">No transfers found</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $transfers->links() }}
    </div>
</div>
@endsection
