@extends('layouts.app')

@section('title', 'Transactions - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Transactions</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-header__title">Transaction History</h1>
    <p class="page-header__subtitle">View all currency exchange transactions</p>
    <div class="page-header__actions">
        <a href="{{ route('transactions.create') }}" class="btn btn--success">+ New Transaction</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $transactions->total() }}</div>
        <div class="stat-card__label">Total Transactions</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $transactions->where('type', 'Buy')->count() }}</div>
        <div class="stat-card__label">Buy Transactions</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $transactions->where('type', 'Sell')->count() }}</div>
        <div class="stat-card__label">Sell Transactions</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $transactions->where('status', 'Pending')->count() }}</div>
        <div class="stat-card__label">Pending Approval</div>
    </div>
</div>

<div class="card">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">All Transactions</h2>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date/Time</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Currency</th>
                    <th>Foreign Amount</th>
                    <th>Rate</th>
                    <th>Local (MYR)</th>
                    <th>Status</th>
                    <th>Teller</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                <tr>
                    <td class="font-mono text-xs">#{{ $transaction->id }}</td>
                    <td class="text-sm text-gray-600">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ e($transaction->customer->full_name ?? 'N/A') }}</td>
                    <td>
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ strtolower($transaction->type->value) === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $transaction->type->label() }}
                        </span>
                    </td>
                    <td class="font-mono text-sm">{{ $transaction->currency_code }}</td>
                    <td class="font-mono text-sm">{{ number_format($transaction->amount_foreign, 4) }}</td>
                    <td class="font-mono text-sm">{{ number_format($transaction->rate, 6) }}</td>
                    <td class="font-mono text-sm font-semibold">{{ number_format($transaction->amount_local, 2) }}</td>
                    <td>
                        @php
                            $statusVariant = match($transaction->status->value) {
                                'Completed' => 'bg-green-100 text-green-800',
                                'Pending', 'PendingApproval' => 'bg-yellow-100 text-yellow-800',
                                'OnHold' => 'bg-orange-100 text-orange-800',
                                'Cancelled', 'Rejected', 'Failed' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-600'
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusVariant }}">
                            {{ $transaction->status->label() }}
                        </span>
                    </td>
                    <td class="text-sm">{{ $transaction->user->username ?? 'N/A' }}</td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('transactions.show', $transaction) }}" class="btn btn--primary btn--sm">View</a>
                            @if($transaction->status->isPending() && auth()->user()->isManager())
                                <form action="{{ route('transactions.approve', $transaction) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn--success btn--sm">Approve</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-12 text-gray-500">
                        No transactions found. <a href="{{ route('transactions.create') }}" class="text-primary-600 hover:underline">Create your first transaction</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-center">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
