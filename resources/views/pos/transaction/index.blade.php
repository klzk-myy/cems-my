@extends('layouts.base')

@section('title', 'Transactions')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Transactions</h5>
            <a href="/pos/transactions/create" class="btn btn-primary btn-sm">New Transaction</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
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
                            <td>{{ $transaction->id }}</td>
                            <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-{{ $transaction->type === 'Buy' ? 'success' : 'info' }}">{{ $transaction->type }}</span></td>
                            <td>{{ $transaction->currency_code }}</td>
                            <td>RM {{ number_format($transaction->amount_local, 2) }}</td>
                            <td>{{ $transaction->customer->name ?? 'N/A' }}</td>
                            <td><span class="badge bg-{{ $transaction->status === 'Completed' ? 'success' : 'warning' }}">{{ $transaction->status }}</span></td>
                            <td>
                                <a href="/pos/transactions/{{ $transaction->id }}" class="btn btn-xs btn-outline-secondary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center">No transactions found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
