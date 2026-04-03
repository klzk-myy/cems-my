@extends('layouts.app')

@section('title', 'Trial Balance - CEMS-MY')

@section('content')
<div class="trial-header">
    <h2>Trial Balance</h2>
    <div class="header-actions">
        <form method="GET" class="date-filter">
            <label for="as_of" style="margin-right: 0.5rem;">As of:</label>
            <input type="date" id="as_of" name="as_of" value="{{ $asOfDate }}" class="form-control" style="width: auto; display: inline-block;">
            <button type="submit" class="btn btn-secondary">Update</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="trial-info">
        <div class="info-item">
            <span class="info-label">Report Date:</span>
            <span class="info-value">{{ now()->format('Y-m-d H:i:s') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">As Of:</span>
            <span class="info-value">{{ $asOfDate }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Total Accounts:</span>
            <span class="info-value">{{ count($trialBalance['accounts']) }}</span>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Account Type</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trialBalance['accounts'] as $account)
            <tr>
                <td><strong>{{ $account['account_code'] }}</strong></td>
                <td>
                    <a href="{{ route('accounting.ledger.account', $account['account_code']) }}">
                        {{ $account['account_name'] }}
                    </a>
                </td>
                <td>{{ $account['account_type'] }}</td>
                <td class="text-right">
                    {{ $account['debit'] != 0 ? 'RM ' . number_format($account['debit'], 2) : '-' }}
                </td>
                <td class="text-right">
                    {{ $account['credit'] != 0 ? 'RM ' . number_format($account['credit'], 2) : '-' }}
                </td>
                <td class="text-right {{ $account['balance'] >= 0 ? 'positive' : 'negative' }}">
                    RM {{ number_format($account['balance'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total:</th>
                <th class="text-right">RM {{ number_format($trialBalance['total_debits'], 2) }}</th>
                <th class="text-right">RM {{ number_format($trialBalance['total_credits'], 2) }}</th>
                <th class="text-right">RM {{ number_format((float) $trialBalance['total_balance'], 2) }}</th>
            </tr>
            <tr>
                <th colspan="3" class="text-right">Difference:</th>
                <th colspan="2" class="text-right {{ abs($trialBalance['total_debits'] - $trialBalance['total_credits']) < 0.01 ? 'balanced' : 'unbalanced' }}">
                    @if(abs($trialBalance['total_debits'] - $trialBalance['total_credits']) < 0.01)
                        ✓ Balanced
                    @else
                        ✗ {{ number_format(abs($trialBalance['total_debits'] - $trialBalance['total_credits']), 2) }}
                    @endif
                </th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Summary Cards -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
    <div class="card summary-card">
        <h3>Assets</h3>
        <p class="amount positive">RM {{ number_format($trialBalance['totals_by_type']['Asset'] ?? 0, 2) }}</p>
    </div>
    <div class="card summary-card">
        <h3>Liabilities</h3>
        <p class="amount">RM {{ number_format(abs($trialBalance['totals_by_type']['Liability'] ?? 0), 2) }}</p>
    </div>
    <div class="card summary-card">
        <h3>Equity</h3>
        <p class="amount {{ ($trialBalance['totals_by_type']['Equity'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
            RM {{ number_format($trialBalance['totals_by_type']['Equity'] ?? 0, 2) }}
        </p>
    </div>
    <div class="card summary-card">
        <h3>Revenue</h3>
        <p class="amount positive">RM {{ number_format($trialBalance['totals_by_type']['Revenue'] ?? 0, 2) }}</p>
    </div>
    <div class="card summary-card">
        <h3>Expenses</h3>
        <p class="amount negative">RM {{ number_format(abs($trialBalance['totals_by_type']['Expense'] ?? 0), 2) }}</p>
    </div>
</div>

@section('styles')
<style>
    .trial-header {
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
    .trial-info {
        display: flex;
        gap: 2rem;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 8px;
    }
    .info-item {
        display: flex;
        flex-direction: column;
    }
    .info-label {
        color: #718096;
        font-size: 0.75rem;
    }
    .info-value {
        font-weight: 600;
        color: #2d3748;
    }
    .form-control {
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
    }
    .text-right {
        text-align: right;
    }
    .positive {
        color: #38a169;
    }
    .negative {
        color: #e53e3e;
    }
    .balanced {
        color: #38a169;
    }
    .unbalanced {
        color: #e53e3e;
    }
    tfoot tr {
        border-top: 2px solid #e2e8f0;
        background: #f7fafc;
    }
    tfoot tr:last-child {
        border-top: none;
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
</style>
@endsection
@endsection
