@extends('layouts.app')

@section('title', 'Currency Position - ' . $position->currency_code . ' - CEMS-MY')

@section('content')
<div style="margin-bottom: 1.5rem;">
    <h2>Currency Position Details</h2>
    <a href="{{ route('stock-cash.index') }}" class="btn btn-secondary">&larr; Back to Stock & Cash</a>
</div>

<div class="card">
    <h3>{{ $position->currency_code }} Position</h3>

    <table>
        <tr>
            <th>Currency</th>
            <td><strong>{{ $position->currency_code }}</strong></td>
        </tr>
        <tr>
            <th>Till ID</th>
            <td>{{ $position->till_id }}</td>
        </tr>
        <tr>
            <th>Current Balance</th>
            <td>{{ number_format($position->balance, 4) }}</td>
        </tr>
        <tr>
            <th>Average Cost Rate</th>
            <td>{{ number_format($position->avg_cost_rate, 6) }}</td>
        </tr>
        <tr>
            <th>Last Valuation Rate</th>
            <td>{{ $position->last_valuation_rate ? number_format($position->last_valuation_rate, 6) : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Unrealized P&L</th>
            <td class="{{ ($position->unrealized_pnl ?? 0) >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                {{ $position->unrealized_pnl ? number_format($position->unrealized_pnl, 2) : '0.00' }} MYR
            </td>
        </tr>
        <tr>
            <th>Last Updated</th>
            <td>{{ $position->updated_at->format('Y-m-d H:i:s') }}</td>
        </tr>
    </table>
</div>

@if(count($transactions) > 0)
<div class="card">
    <h3>Related Transactions</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $txn)
            <tr>
                <td>{{ $txn->id }}</td>
                <td>{{ $txn->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $txn->customer->name ?? 'N/A' }}</td>
                <td>{{ $txn->type->value }}</td>
                <td>{{ number_format($txn->amount_foreign, 2) }} {{ $txn->currency_code }}</td>
                <td>{{ number_format($txn->rate, 4) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
