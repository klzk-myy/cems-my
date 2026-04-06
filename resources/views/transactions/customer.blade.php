@extends('layouts.app')

@section('title', 'Customer Transactions - CEMS-MY')

@section('styles')
<style>
    .customer-header {
        margin-bottom: 1.5rem;
    }
    .customer-info {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .transactions-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .transactions-header p {
        color: #718096;
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
<div class="customer-header">
    <h2>Customer Transactions</h2>
    <p>View transaction history for {{ $customer->full_name ?? 'Customer' }}</p>
</div>

@if($customer)
<div class="customer-info">
    <strong>{{ $customer->full_name }}</strong> |
    ID: {{ $customer->id_type ?? 'N/A' }} - {{ $customer->id_number ?? 'N/A' }} |
    Risk Rating: {{ $customer->risk_rating ?? 'N/A' }}
</div>
@endif

<div class="card">
    <h3>Transaction History</h3>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date/Time</th>
                <th>Type</th>
                <th>Currency</th>
                <th>Foreign Amount</th>
                <th>Rate</th>
                <th>Local (MYR)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
            <tr>
                <td>#{{ $transaction->id }}</td>
                <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
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
                <td>
                    <a href="/transactions/{{ $transaction->id }}" class="btn btn-primary" style="font-size: 0.75rem;">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 2rem; color: #718096;">
                    No transactions found for this customer.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
