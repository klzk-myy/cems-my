@extends('layouts.app')

@section('title', 'Profit & Loss Statement - CEMS-MY')

@section('content')
<div class="pl-header">
    <h2>Profit & Loss Statement</h2>
    <div class="header-actions">
        <form method="GET" class="date-filter">
            <label for="from" style="margin-right: 0.5rem;">From:</label>
            <input type="date" id="from" name="from" value="{{ $fromDate }}" class="form-control" style="width: auto; display: inline-block;">
            <label for="to" style="margin: 0 0.5rem;">To:</label>
            <input type="date" id="to" name="to" value="{{ $toDate }}" class="form-control" style="width: auto; display: inline-block;">
            <button type="submit" class="btn btn-secondary">Update</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="statement-header">
        <h3>PROFIT AND LOSS STATEMENT</h3>
        <p class="period">Period: {{ $fromDate }} to {{ $toDate }}</p>
        <p class="generated">Generated: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
    
    <!-- Revenue Section -->
    <div class="section revenue">
        <h4>REVENUE</h4>
        @if(count($pl['revenues']) > 0)
        @foreach($pl['revenues'] as $account)
            <div class="account-row">
                <span class="account-name">{{ $account['account_name'] }}</span>
                <span class="account-amount positive">RM {{ number_format($account['amount'], 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="account-row">
                <span class="account-name">No revenue accounts with activity</span>
                <span class="account-amount">-</span>
            </div>
        @endif
        <div class="section-total">
            <span class="total-label">Total Revenue</span>
            <span class="total-amount positive">RM {{ number_format($pl['total_revenue'], 2) }}</span>
        </div>
    </div>
    
    <!-- Expenses Section -->
    <div class="section expenses">
        <h4>EXPENSES</h4>
        @if(count($pl['expenses']) > 0)
            @foreach($pl['expenses'] as $account)
            <div class="account-row">
                <span class="account-name">{{ $account['account_name'] }}</span>
                <span class="account-amount negative">RM {{ number_format(abs($account['amount']), 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="account-row">
                <span class="account-name">No expense accounts with activity</span>
                <span class="account-amount">-</span>
            </div>
        @endif
        <div class="section-total">
            <span class="total-label">Total Expenses</span>
            <span class="total-amount negative">RM {{ number_format(abs($pl['total_expenses']), 2) }}</span>
        </div>
    </div>
    
    <!-- Net Profit/Loss -->
    <div class="net-result {{ $pl['net_profit'] >= 0 ? 'profit' : 'loss' }}">
        <span class="net-label">NET {{ $pl['net_profit'] >= 0 ? 'PROFIT' : 'LOSS' }}</span>
        <span class="net-amount">
            RM {{ number_format(abs($pl['net_profit']), 2) }}
        </span>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
    <div class="card summary-card">
        <h3>Total Revenue</h3>
        <p class="amount positive">RM {{ number_format($pl['total_revenue'], 2) }}</p>
    </div>
    <div class="card summary-card">
        <h3>Total Expenses</h3>
        <p class="amount negative">RM {{ number_format(abs($pl['total_expenses']), 2) }}</p>
    </div>
    <div class="card summary-card {{ $pl['net_profit'] >= 0 ? 'profit-card' : 'loss-card' }}">
        <h3>Net {{ $pl['net_profit'] >= 0 ? 'Profit' : 'Loss' }}</h3>
        <p class="amount">RM {{ number_format(abs($pl['net_profit']), 2) }}</p>
    </div>
</div>

@section('styles')
<style>
    .pl-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .header-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .form-control {
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
    }
    .statement-header {
        text-align: center;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 1.5rem;
    }
    .statement-header h3 {
        margin: 0;
        color: #1a365d;
        font-size: 1.5rem;
    }
    .period {
        color: #718096;
        margin: 0.5rem 0;
    }
    .generated {
        color: #a0aec0;
        font-size: 0.875rem;
        margin: 0;
    }
    .section {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .section h4 {
        color: #1a365d;
        margin-bottom: 1rem;
        font-size: 1rem;
        text-transform: uppercase;
    }
    .account-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        padding-left: 1rem;
    }
    .account-name {
        color: #4a5568;
    }
    .account-amount {
        font-family: monospace;
        font-size: 0.9375rem;
    }
    .positive {
        color: #38a169;
    }
    .negative {
        color: #e53e3e;
    }
    .section-total {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        margin-top: 0.5rem;
        border-top: 1px solid #e2e8f0;
        font-weight: 600;
    }
    .total-label {
        color: #2d3748;
    }
    .total-amount {
        font-family: monospace;
    }
    .net-result {
        display: flex;
        justify-content: space-between;
        padding: 1.5rem;
        background: #f7fafc;
        border-radius: 8px;
        margin-top: 1.5rem;
    }
    .net-result.profit {
        background: #c6f6d5;
    }
    .net-result.loss {
        background: #fed7d7;
    }
    .net-label {
        font-size: 1.25rem;
        font-weight: 700;
    }
    .net-result.profit .net-label {
        color: #22543d;
    }
    .net-result.loss .net-label {
        color: #c53030;
    }
    .net-amount {
        font-size: 1.5rem;
        font-weight: 700;
        font-family: monospace;
    }
    .summary-card {
        text-align: center;
        padding: 1.5rem;
    }
    .summary-card h3 {
        color: #718096;
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }
    .amount {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .profit-card {
        background: #c6f6d5;
    }
    .loss-card {
        background: #fed7d7;
    }
</style>
@endsection
@endsection
