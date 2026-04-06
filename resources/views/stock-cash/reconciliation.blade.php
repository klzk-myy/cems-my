@extends('layouts.app')

@section('title', 'Till Reconciliation Report')

@section('styles')
<style>
    .report-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        padding: 2rem;
        color: white;
        margin-bottom: 1.5rem;
    }
    .report-header h2 {
        margin-bottom: 0.5rem;
        font-size: 1.5rem;
    }
    .report-header p {
        opacity: 0.9;
        margin-bottom: 0.25rem;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .summary-box {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        border-left: 4px solid #667eea;
    }
    .summary-box.positive {
        border-left-color: #38a169;
    }
    .summary-box.negative {
        border-left-color: #e53e3e;
    }
    .summary-box.warning {
        border-left-color: #dd6b20;
    }
    .summary-box .value {
        font-size: 1.5rem;
        font-weight: bold;
        color: #2d3748;
    }
    .summary-box .label {
        color: #718096;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    .reconciliation-flow {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: 2rem 0;
        padding: 1.5rem;
        background: #f7fafc;
        border-radius: 8px;
    }
    .flow-item {
        text-align: center;
        padding: 1rem 1.5rem;
        background: white;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        min-width: 120px;
    }
    .flow-item .amount {
        font-size: 1.25rem;
        font-weight: bold;
        color: #2d3748;
    }
    .flow-item .label {
        font-size: 0.75rem;
        color: #718096;
    }
    .flow-operator {
        font-size: 1.5rem;
        color: #718096;
        font-weight: bold;
    }
    .variance-display {
        padding: 1rem 2rem;
        border-radius: 6px;
        font-weight: bold;
        font-size: 1.25rem;
    }
    .variance-zero {
        background: #c6f6d5;
        color: #276749;
    }
    .variance-positive {
        background: #fed7d7;
        color: #c53030;
    }
    .variance-negative {
        background: #fed7d7;
        color: #c53030;
    }
    .status-closed {
        background: #c6f6d5;
        color: #276749;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
    }
    .status-open {
        background: #fefcbf;
        color: #744210;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
    }
</style>
@endsection

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

    <div style="text-align: center; margin-top: 1.5rem;">
        <p><strong>Actual Closing:</strong> {{ $reconciliation['actual_closing'] !== null ? number_format($reconciliation['actual_closing'], 2) : 'Not yet closed' }}</p>
        <p style="margin-top: 1rem;"><strong>Variance:</strong></p>
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
                <td colspan="7" style="text-align: center; color: #718096;">
                    No transactions found for this till and date.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="text-align: center; margin-top: 1.5rem;">
    <a href="{{ route('stock-cash.index') }}" class="btn btn-secondary">Back to Stock & Cash</a>
    <button onclick="window.print()" class="btn btn-primary">Print Report</button>
</div>
@endsection
