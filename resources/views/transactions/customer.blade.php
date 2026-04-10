@extends('layouts.app')

@section('title', 'Customer Transactions - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Customer Transactions</h2>
    <p class="text-gray-500 text-sm">View transaction history for {{ $customer->full_name ?? 'Customer' }}</p>
</div>

@if($customer)
<div class="bg-gray-50 rounded-lg p-4 mb-4">
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
                <td class="{{ strtolower($transaction->type->value) === 'buy' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">{{ $transaction->type->label() }}</td>
                <td>{{ $transaction->currency_code }}</td>
                <td>{{ number_format($transaction->amount_foreign, 4) }}</td>
                <td>{{ number_format($transaction->rate, 6) }}</td>
                <td>{{ number_format($transaction->amount_local, 2) }}</td>
                <td>
                    @php
                        $statusClass = match($transaction->status->value) {
                            'Completed' => 'bg-green-100 text-green-800',
                            'Pending' => 'bg-orange-100 text-orange-800',
                            'OnHold' => 'bg-red-100 text-red-800',
                            default => 'bg-orange-100 text-orange-800'
                        };
                    @endphp
                    <span class="status-badge {{ $statusClass }} px-2 py-1 rounded text-xs">{{ $transaction->status->label() }}</span>
                </td>
                <td>
                    <a href="/transactions/{{ $transaction->id }}" class="btn btn-primary text-xs px-2 py-1">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-8 text-gray-500">
                    No transactions found for this customer.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="flex justify-center gap-2 mt-4">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
