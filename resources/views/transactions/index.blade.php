@extends('layouts.app')

@section('title', 'Transactions - CEMS-MY')

@section('styles')
<style>
    .transactions-header {
        margin-bottom: 1.5rem;
    }
    .transactions-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .transactions-header p {
        color: #718096;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        padding: 1.5rem;
        color: white;
        text-align: center;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
    }
    .stat-label {
        margin-top: 0.5rem;
        opacity: 0.9;
    }
    .stat-buy { background: linear-gradient(135deg, #38a169 0%, #2f855a 100%); }
    .stat-sell { background: linear-gradient(135deg, #dd6b20 0%, #c05621 100%); }
    .stat-pending { background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%); }

    .actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .type-buy { color: #38a169; font-weight: 600; }
    .type-sell { color: #e53e3e; font-weight: 600; }

    .status-completed { background: #c6f6d5; color: #276749; }
    .status-pending { background: #feebc8; color: #c05621; }
    .status-onhold { background: #fed7d7; color: #c53030; }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .pagination a, .pagination span {
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
    }
    .pagination a { background: #e2e8f0; color: #4a5568; }
    .pagination span { background: #3182ce; color: white; }
</style>
@endsection

@section('content')
<div class="transactions-header">
    <h2>Transaction History</h2>
    <p>View all currency exchange transactions</p>
</div>

<!-- Stats -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card">
        <div class="stat-value">{{ $transactions->total() }}</div>
        <div class="stat-label">Total Transactions</div>
    </div>
    <div class="stat-card stat-buy">
        <div class="stat-value">{{ $transactions->where('type', 'Buy')->count() }}</div>
        <div class="stat-label">Buy Transactions</div>
    </div>
    <div class="stat-card stat-sell">
        <div class="stat-value">{{ $transactions->where('type', 'Sell')->count() }}</div>
        <div class="stat-label">Sell Transactions</div>
    </div>
    <div class="stat-card stat-pending">
        <div class="stat-value">{{ $transactions->where('status', 'Pending')->count() }}</div>
        <div class="stat-label">Pending Approval</div>
    </div>
</div>

<div class="card">
<div class="actions-bar">
<h2>All Transactions</h2>
<a href="/transactions/create" class="btn btn-success" aria-label="Create new transaction">+ New Transaction</a>
</div>

<div class="table-responsive" role="region" aria-label="Transaction list" tabindex="0">
<table role="table">
<thead role="rowgroup">
<tr role="row">
<th scope="col" role="columnheader">ID</th>
<th scope="col" role="columnheader">Date/Time</th>
<th scope="col" role="columnheader">Customer</th>
<th scope="col" role="columnheader">Type</th>
<th scope="col" role="columnheader">Currency</th>
<th scope="col" role="columnheader">Foreign Amount</th>
<th scope="col" role="columnheader">Rate</th>
<th scope="col" role="columnheader">Local (MYR)</th>
<th scope="col" role="columnheader">Status</th>
<th scope="col" role="columnheader">Teller</th>
<th scope="col" role="columnheader">Actions</th>
</tr>
</thead>
<tbody role="rowgroup">
@forelse($transactions as $transaction)
<tr role="row">
<td>#{{ $transaction->id }}</td>
<td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
<td>{{ e($transaction->customer->full_name ?? 'N/A') }}</td>
                <td class="type-{{ strtolower($transaction->type->value) }}">{{ $transaction->type->label() }}</td>
                <td>{{ $transaction->currency_code }}</td>
                <td>{{ number_format($transaction->amount_foreign, 4) }}</td>
                <td>{{ number_format($transaction->rate, 6) }}</td>
                <td>{{ number_format($transaction->amount_local, 2) }}</td>
                <td>
@php
                        $statusClass = match($transaction->status->value) {
                            'Completed' => 'status-completed',
                            'Pending' => 'status-pending',
                            'OnHold' => 'status-onhold',
                            default => 'status-pending'
                        };
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $transaction->status->label() }}</span>
                </td>
                <td>{{ $transaction->user->username ?? 'N/A' }}</td>
                <td>
                    <a href="/transactions/{{ $transaction->id }}" class="btn btn-primary" style="font-size: 0.75rem;">View</a>
                    @if($transaction->status->isPending() && auth()->user()->isManager())
                        <form action="/transactions/{{ $transaction->id }}/approve" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success" style="font-size: 0.75rem;">Approve</button>
                        </form>
                    @endif
                </td>
            </tr>
@empty
<tr role="row">
<td colspan="11" style="text-align: center; padding: 2rem; color: #718096;">
No transactions found. <a href="/transactions/create">Create your first transaction</a>.
</td>
</tr>
@endforelse
</tbody>
</table>
</div>

<div class="pagination" role="navigation" aria-label="Pagination">
{{ $transactions->links() }}
</div>
</div>
@endsection
