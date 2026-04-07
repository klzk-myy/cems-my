@extends('layouts.app')

@section('title', 'Balance Sheet - CEMS-MY')

@section('content')
<div class="bs-header">
    <h2>Balance Sheet</h2>
    <div class="header-actions">
        <form method="GET" class="date-filter">
            <label for="as_of" style="margin-right: 0.5rem;">As of:</label>
            <input type="date" id="as_of" name="as_of" value="{{ $asOfDate }}" class="form-control" style="width: auto; display: inline-block;">
            <button type="submit" class="btn btn-secondary">Update</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="statement-header">
        <h3>BALANCE SHEET</h3>
        <p class="period">As of: {{ $asOfDate }}</p>
        <p class="generated">Generated: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
    
    <!-- Assets Section -->
    <div class="section assets">
        <h4>ASSETS</h4>
        @if(count($balanceSheet['assets']) > 0)
            @foreach($balanceSheet['assets'] as $account)
            <div class="account-row">
                <span class="account-name">{{ $account['account_name'] }}</span>
                <span class="account-amount">RM {{ number_format($account['balance'], 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="account-row">
                <span class="account-name">No asset accounts with balance</span>
                <span class="account-amount">-</span>
            </div>
        @endif
        <div class="section-total">
            <span class="total-label">Total Assets</span>
            <span class="total-amount">RM {{ number_format($balanceSheet['total_assets'], 2) }}</span>
        </div>
    </div>
    
    <!-- Liabilities Section -->
    <div class="section liabilities">
        <h4>LIABILITIES</h4>
        @if(count($balanceSheet['liabilities']) > 0)
            @foreach($balanceSheet['liabilities'] as $account)
            <div class="account-row">
                <span class="account-name">{{ $account['account_name'] }}</span>
                <span class="account-amount">RM {{ number_format($account['balance'], 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="account-row">
                <span class="account-name">No liability accounts with balance</span>
                <span class="account-amount">-</span>
            </div>
        @endif
        <div class="section-total">
            <span class="total-label">Total Liabilities</span>
            <span class="total-amount">RM {{ number_format($balanceSheet['total_liabilities'], 2) }}</span>
        </div>
    </div>
    
    <!-- Equity Section -->
    <div class="section equity">
        <h4>EQUITY</h4>
        @if(count($balanceSheet['equity']) > 0)
            @foreach($balanceSheet['equity'] as $account)
            <div class="account-row">
                <span class="account-name">{{ $account['account_name'] }}</span>
                <span class="account-amount">RM {{ number_format($account['balance'], 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="account-row">
                <span class="account-name">No equity accounts with balance</span>
                <span class="account-amount">-</span>
            </div>
        @endif
        <div class="section-total">
            <span class="total-label">Total Equity</span>
            <span class="total-amount">RM {{ number_format($balanceSheet['total_equity'], 2) }}</span>
        </div>
    </div>
    
    <!-- Balance Check -->
    <div class="balance-check {{ $balanceSheet['is_balanced'] ? 'balanced' : 'unbalanced' }}">
        <div class="balance-row">
            <span class="balance-label">Total Assets</span>
            <span class="balance-amount">RM {{ number_format($balanceSheet['total_assets'], 2) }}</span>
        </div>
        <div class="balance-row">
            <span class="balance-label">Total Liabilities + Equity</span>
            <span class="balance-amount">RM {{ number_format($balanceSheet['liabilities_plus_equity'], 2) }}</span>
        </div>
        <div class="balance-row difference">
            <span class="balance-label">Difference</span>
            <span class="balance-amount">
                @if($balanceSheet['is_balanced'])
                    ✓ Balanced
                @else
                    {{ number_format(abs((float) $balanceSheet['total_assets'] - (float) $balanceSheet['liabilities_plus_equity']), 2) }}
                @endif
            </span>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
    <div class="card summary-card">
        <h3>Total Assets</h3>
        <p class="amount">RM {{ number_format($balanceSheet['total_assets'], 2) }}</p>
    </div>
    <div class="card summary-card">
        <h3>Total Liabilities</h3>
        <p class="amount">RM {{ number_format($balanceSheet['total_liabilities'], 2) }}</p>
    </div>
    <div class="card summary-card">
        <h3>Total Equity</h3>
        <p class="amount">RM {{ number_format($balanceSheet['total_equity'], 2) }}</p>
    </div>
    <div class="card summary-card {{ $balanceSheet['is_balanced'] ? 'balanced-card' : 'unbalanced-card' }}">
        <h3>Balance Status</h3>
        <p class="amount">{{ $balanceSheet['is_balanced'] ? '✓ Balanced' : '✗ Unbalanced' }}</p>
    </div>
</div>

@section('styles')
<style>
    .bs-header {
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
    .balance-check {
        padding: 1.5rem;
        background: #f7fafc;
        border-radius: 8px;
        margin-top: 1.5rem;
    }
    .balance-check.balanced {
        background: #c6f6d5;
    }
    .balance-check.unbalanced {
        background: #fed7d7;
    }
    .balance-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
    }
    .balance-label {
        font-weight: 500;
    }
    .balance-amount {
        font-family: monospace;
        font-weight: 600;
    }
    .difference {
        border-top: 1px solid currentColor;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        font-size: 1.125rem;
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
    .balanced-card {
        background: #c6f6d5;
    }
    .unbalanced-card {
        background: #fed7d7;
    }
</style>
@endsection
@endsection
