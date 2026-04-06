@extends('layouts.app')

@section('title', 'Ledger - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Chart of Accounts / Ledger</h2>
    <p>View all accounts and drill down to individual ledgers</p>
</div>

<div class="card">
    <h2>All Accounts</h2>

    @if(count($trialBalance['accounts']) > 0)
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Account Name</th>
                <th>Type</th>
                <th style="text-align: right;">Debit Balance</th>
                <th style="text-align: right;">Credit Balance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trialBalance['accounts'] as $account)
            <tr>
                <td><strong>{{ $account['account_code'] }}</strong></td>
                <td>{{ $account['account_name'] }}</td>
                <td>
                    <span class="status-badge {{ $account['account_type'] === 'Asset' ? 'status-active' : ($account['account_type'] === 'Liability' ? 'status-flagged' : 'status-pending') }}">
                        {{ $account['account_type'] }}
                    </span>
                </td>
                <td style="text-align: right;">{{ number_format($account['debit'], 2) }}</td>
                <td style="text-align: right;">{{ number_format($account['credit'], 2) }}</td>
                <td>
                    <a href="{{ route('accounting.ledger.account', $account['account_code']) }}" class="btn btn-sm btn-primary">View Ledger</a>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot style="background: #f7fafc; font-weight: 600;">
            <tr>
                <td colspan="3">TOTAL</td>
                <td style="text-align: right;">{{ number_format($trialBalance['total_debits'], 2) }}</td>
                <td style="text-align: right;">{{ number_format($trialBalance['total_credits'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="alert alert-info">
        No chart of accounts found. Please run database migrations.
    </div>
    @endif
</div>

<div class="card">
    <h2>Quick Links</h2>
    <div style="display: flex; gap: 1rem;">
        <a href="{{ route('accounting.trial-balance') }}" class="btn btn-primary">Trial Balance</a>
        <a href="{{ route('accounting.profit-loss') }}" class="btn btn-success">Profit & Loss</a>
        <a href="{{ route('accounting.balance-sheet') }}" class="btn btn-info">Balance Sheet</a>
    </div>
</div>
@endsection
