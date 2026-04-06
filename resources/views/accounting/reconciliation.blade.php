@extends('layouts.app')

@section('title', 'Bank Reconciliation - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Bank Reconciliation</h2>
    <p>Reconcile cash accounts with bank statements</p>
</div>

<div class="card">
    <h2>Select Account</h2>
    <form method="GET" action="{{ route('accounting.reconciliation') }}">
        <div style="display: flex; gap: 1rem; align-items: flex-end;">
            <div>
                <label for="account">Cash Account</label>
                <select name="account" id="account" class="form-control">
                    @foreach($cashAccounts as $account)
                        <option value="{{ $account->account_code }}" {{ $account->account_code == request('account') ? 'selected' : '' }}>
                            {{ $account->account_code }} - {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">View Reconciliation</button>
        </div>
    </form>
</div>

@if(isset($report))
<div class="card">
    <h2>Reconciliation Report</h2>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;">
        <div class="summary-box">
            <div class="summary-value">{{ number_format($report['book_balance'] ?? 0, 2) }}</div>
            <div class="summary-label">Book Balance (MYR)</div>
        </div>
        <div class="summary-box">
            <div class="summary-value">{{ number_format($report['outstanding_checks'] ?? 0, 2) }}</div>
            <div class="summary-label">Outstanding Checks</div>
        </div>
        <div class="summary-box">
            <div class="summary-value">{{ number_format($report['outstanding_deposits'] ?? 0, 2) }}</div>
            <div class="summary-label">Outstanding Deposits</div>
        </div>
        <div class="summary-box">
            <div class="summary-value {{ ($report['adjusted_balance'] ?? 0) >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                {{ number_format($report['adjusted_balance'] ?? 0, 2) }}
            </div>
            <div class="summary-label">Adjusted Balance</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Outstanding Checks -->
        <div>
            <h3 style="color: #e53e3e;">Outstanding Checks</h3>
            @if(count($report['outstanding_checks_list'] ?? []) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['outstanding_checks_list'] as $item)
                    <tr>
                        <td>{{ $item['date'] }}</td>
                        <td>{{ $item['reference'] }}</td>
                        <td style="text-align: right;">{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="color: #718096;">No outstanding checks</p>
            @endif
        </div>

        <!-- Outstanding Deposits -->
        <div>
            <h3 style="color: #38a169;">Outstanding Deposits</h3>
            @if(count($report['outstanding_deposits_list'] ?? []) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['outstanding_deposits_list'] as $item)
                    <tr>
                        <td>{{ $item['date'] }}</td>
                        <td>{{ $item['reference'] }}</td>
                        <td style="text-align: right;">{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="color: #718096;">No outstanding deposits</p>
            @endif
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="alert alert-info">
        Select a cash account above to view the reconciliation report.
    </div>
</div>
@endif
@endsection
