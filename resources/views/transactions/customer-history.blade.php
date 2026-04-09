@extends('layouts.app')

@section('title', 'Customer Transaction History - ' . $customer->full_name)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Customer Transaction History</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customers.show', $customer) }}">{{ $customer->full_name }}</a></li>
                    <li class="breadcrumb-item active">Transaction History</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Customer Info Card --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Name:</strong><br>
                    {{ $customer->full_name }}
                </div>
                <div class="col-md-3">
                    <strong>ID Type:</strong><br>
                    {{ $customer->id_type }}
                </div>
                <div class="col-md-3">
                    <strong>Risk Rating:</strong><br>
                    <span class="badge bg-{{ $customer->risk_rating === 'High' ? 'danger' : ($customer->risk_rating === 'Medium' ? 'warning' : 'success') }}">
                        {{ $customer->risk_rating }}
                    </span>
                </div>
                <div class="col-md-3">
                    <strong>CDD Level:</strong><br>
                    {{ $customer->cdd_level }}
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('customers.history', $customer) }}" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ $validated['date_from'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="{{ $validated['date_to'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="date" {{ ($validated['sort_by'] ?? '') === 'date' ? 'selected' : '' }}>Date</option>
                        <option value="amount" {{ ($validated['sort_by'] ?? '') === 'amount' ? 'selected' : '' }}>Amount</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort_order" class="form-label">Order</label>
                    <select class="form-select" id="sort_order" name="sort_order">
                        <option value="desc" {{ ($validated['sort_order'] ?? '') === 'desc' ? 'selected' : '' }}>Descending</option>
                        <option value="asc" {{ ($validated['sort_order'] ?? '') === 'asc' ? 'selected' : '' }}>Ascending</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Statistics --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary">{{ number_format($summary['total_transactions']) }}</h3>
                    <small class="text-muted">Total Transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success">{{ number_format($summary['total_buy_count']) }}</h3>
                    <small class="text-muted">Buy Transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info">{{ number_format($summary['total_sell_count']) }}</h3>
                    <small class="text-muted">Sell Transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success">RM {{ number_format($summary['total_buy_amount'], 2) }}</h3>
                    <small class="text-muted">Total Buy (MYR)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info">RM {{ number_format($summary['total_sell_amount'], 2) }}</h3>
                    <small class="text-muted">Total Sell (MYR)</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Export Buttons --}}
    <div class="mb-3">
        <form method="GET" action="{{ route('customers.export', $customer) }}" class="d-inline">
            @foreach($validated as $key => $value)
                @if($value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="hidden" name="format" value="CSV">
            <button type="submit" class="btn btn-outline-success me-2">
                <i class="fas fa-file-csv me-1"></i>Export CSV
            </button>
        </form>
        <form method="GET" action="{{ route('customers.export', $customer) }}" class="d-inline">
            @foreach($validated as $key => $value)
                @if($value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="hidden" name="format" value="PDF">
            <button type="submit" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf me-1"></i>Export PDF
            </button>
        </form>
    </div>

    {{-- Transactions Table --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Transaction Details</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Currency</th>
                            <th>Foreign Amount</th>
                            <th>MYR Amount</th>
                            <th>Rate</th>
                            <th>Status</th>
                            <th>Processed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->id }}</td>
                            <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <span class="badge bg-{{ $transaction->type->value === 'buy' ? 'success' : 'info' }}">
                                    {{ $transaction->type->label() }}
                                </span>
                            </td>
                            <td>{{ $transaction->currency_code }}</td>
                            <td>{{ number_format($transaction->amount_foreign, 2) }}</td>
                            <td>RM {{ number_format($transaction->amount_local, 2) }}</td>
                            <td>{{ number_format($transaction->rate, 6) }}</td>
                            <td>
                                <span class="badge bg-{{ $transaction->status->value === 'completed' ? 'success' : ($transaction->status->value === 'pending' ? 'warning' : 'secondary') }}">
                                    {{ $transaction->status->label() }}
                                </span>
                            </td>
                            <td>{{ $transaction->user?->name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No transactions found for this customer.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
