@extends('layouts.app')

@section('title', 'Transactions - CEMS-MY')

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
    <h2 style="font-family: var(--font-heading); font-size: 1.125rem; margin-bottom: 1rem;">All Transactions</h2>

    <div style="overflow-x: auto;">
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
                    <td>#{{ $transaction->id }}</td>
                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ e($transaction->customer->full_name ?? 'N/A') }}</td>
                    <td>
                        @php
                            $typeClass = strtolower($transaction->type->value) === 'buy' ? 'status-badge--completed' : 'status-badge--flagged';
                        @endphp
                        <span class="status-badge {{ $typeClass }}">{{ $transaction->type->label() }}</span>
                    </td>
                    <td>{{ $transaction->currency_code }}</td>
                    <td>{{ number_format($transaction->amount_foreign, 4) }}</td>
                    <td>{{ number_format($transaction->rate, 6) }}</td>
                    <td>{{ number_format($transaction->amount_local, 2) }}</td>
                    <td>
                        @php
                            $statusClass = match($transaction->status->value) {
                                'Completed' => 'status-badge--completed',
                                'Pending' => 'status-badge--pending',
                                'OnHold' => 'status-badge--flagged',
                                default => 'status-badge--pending'
                            };
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $transaction->status->label() }}</span>
                    </td>
                    <td>{{ $transaction->user->username ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('transactions.show', $transaction) }}" class="btn btn--sm btn--primary">View</a>
                        @if($transaction->status->isPending() && auth()->user()->isManager())
                            <form action="{{ route('transactions.approve', $transaction) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn--sm btn--success">Approve</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 2rem; color: var(--color-gray-500);">
                        No transactions found. <a href="{{ route('transactions.create') }}" style="color: var(--color-primary-lighter);">Create your first transaction</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem; display: flex; justify-content: center;">
        {{ $transactions->links() }}
    </div>
</div>
@endsection