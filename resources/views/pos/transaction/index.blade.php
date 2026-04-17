@extends('layouts.base')

@section('title', 'POS Transactions')

@section('header-title')
<div>
    <h1 class="text-2xl font-semibold text-[--color-ink]">Transactions</h1>
    <p class="text-sm text-[--color-ink-muted]">Point of Sale transaction history</p>
</div>
@endsection

@section('header-actions')
<a href="/pos/transactions/create" class="btn btn-primary">New Transaction</a>
@endsection

@section('content')
<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Currency</th>
                    <th>Amount (MYR)</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                <tr>
                    <td class="font-mono">#{{ $transaction->id }}</td>
                    <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                    <td>
                        @if($transaction->type === 'Buy')
                            <span class="badge badge-success">Buy</span>
                        @else
                            <span class="badge badge-info">Sell</span>
                        @endif
                    </td>
                    <td class="font-medium">{{ $transaction->currency_code }}</td>
                    <td class="font-mono">RM {{ number_format($transaction->amount_local, 2) }}</td>
                    <td>{{ $transaction->customer->name ?? 'N/A' }}</td>
                    <td>
                        @if($transaction->status === 'Completed')
                            <span class="badge badge-success">Completed</span>
                        @else
                            <span class="badge badge-warning">{{ $transaction->status }}</span>
                        @endif
                    </td>
                    <td>
                        <a href="/pos/transactions/{{ $transaction->id }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-12 text-[--color-ink-muted]">No transactions found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer">
        {{ $transactions->links() }}
    </div>
    @endif
</div>
@endsection
