@extends('layouts.app')

@section('title', 'Till Reconciliation Report')

@section('content')
<div class="report-header">
    <h2>Till Reconciliation Report</h2>
    <p><strong>Till ID:</strong> {{ $tillId }}</p>
    <p><strong>Currency:</strong> {{ $tillBalance->currency->name }} ({{ $tillBalance->currency_code }})</p>
    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</p>
    <p><strong>Status:</strong>
        <span class="{{ $reconciliation['is_closed'] ? 'status-closed' : 'status-open' }}">
            {{ $reconciliation['is_closed'] ? 'Closed' : 'Open' }}
        </span>
    </p>
</div>

<h3>Summary</h3>
<div class="summary-grid">
    <div class="summary-box">
        <div class="value">{{ number_format($reconciliation['opening_balance'], 4) }}</div>
        <div class="label">Opening Balance</div>
    </div>
    <div class="summary-box positive">
        <div class="value">{{ $reconciliation['purchases']['count'] }}</div>
        <div class="label">Purchase Transactions</div>
    </div>
    <div class="summary-box positive">
        <div class="value">{{ number_format($reconciliation['purchases']['total'], 2) }}</div>
        <div class="label">Total Purchases</div>
    </div>
    <div class="summary-box negative">
        <div class="value">{{ $reconciliation['sales']['count'] }}</div>
        <div class="label">Sale Transactions</div>
    </div>
    <div class="summary-box negative">
        <div class="value">{{ number_format($reconciliation['sales']['total'], 2) }}</div>
        <div class="label">Total Sales</div>
    </div>
    <div class="summary-box {{ $reconciliation['variance'] == 0 ? 'positive' : 'warning' }}">
        <div class="value">{{ number_format($reconciliation['variance'] ?? 0, 2) }}</div>
        <div class="label">Variance</div>
    </div>
</div>

<div class="card">
    <h3>Reconciliation Flow</h3>
    <div class="reconciliation-flow">
        <div class="flow-item">
            <div class="amount">{{ number_format($reconciliation['opening_balance'], 4) }}</div>
            <div class="label">Opening Balance</div>
        </div>
        <div class="flow-operator">+</div>
        <div class="flow-item">
            <div class="amount">{{ number_format($reconciliation['purchases']['total'], 2) }}</div>
            <div class="label">Purchases</div>
        </div>
        <div class="flow-operator">-</div>
        <div class="flow-item">
            <div class="amount">{{ number_format($reconciliation['sales']['total'], 2) }}</div>
            <div class="label">Sales</div>
        </div>
        <div class="flow-operator">=</div>
        <div class="flow-item">
            <div class="amount">{{ number_format($reconciliation['expected_closing'], 2) }}</div>
            <div class="label">Expected Closing</div>
        </div>
    </div>

    <div class="text-center mt-6">
        <p><strong>Actual Closing:</strong> {{ $reconciliation['actual_closing'] !== null ? number_format($reconciliation['actual_closing'], 2) : 'Not yet closed' }}</p>
        <p class="mt-4"><strong>Variance:</strong></p>
        <div class="variance-display {{ $reconciliation['variance'] == 0 ? 'variance-zero' : ($reconciliation['variance'] > 0 ? 'variance-positive' : 'variance-negative') }}">
            {{ $reconciliation['variance'] !== null ? number_format($reconciliation['variance'], 2) : 'N/A' }}
        </div>
    </div>
</div>

<div class="card">
    <h3>Transaction Details</h3>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Transaction #</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Amount (Foreign)</th>
                <th>Amount (MYR)</th>
                <th>Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
            <tr>
                <td>{{ $transaction->created_at->format('H:i:s') }}</td>
                <td>#{{ $transaction->id }}</td>
                <td>{{ $transaction->customer->full_name ?? 'N/A' }}</td>
                <td>
                    <span class="{{ $transaction->type === 'Buy' ? 'status-closed' : 'status-open' }}">
                        {{ $transaction->type }}
                    </span>
                </td>
                <td>{{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }}</td>
                <td>{{ number_format($transaction->amount_local, 2) }}</td>
                <td>{{ number_format($transaction->rate, 6) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-gray">
                    No transactions found for this till and date.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="text-center mt-6">
    <a href="{{ route('stock-cash.index') }}" class="btn btn-secondary">Back to Stock & Cash</a>
    <button onclick="window.print()" class="btn btn-primary">Print Report</button>
</div>
@endsection
