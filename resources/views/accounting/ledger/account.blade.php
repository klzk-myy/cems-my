@extends('layouts.app')

@section('title', 'Account Ledger - CEMS-MY')

@section('content')
<div class="ledger-header">
    <h2>Account Ledger</h2>
    <div class="header-actions">
        <a href="{{ route('accounting.ledger') }}" class="btn btn-secondary">Back to Trial Balance</a>
    </div>
</div>

<div class="card">
    <h2>{{ $ledger['account']['account_code'] }} - {{ $ledger['account']['account_name'] }}</h2>
    <p class="account-type">{{ $ledger['account']['account_type'] }}</p>
    
    <!-- Date Filter -->
    <form method="GET" class="date-filter">
        <div class="form-row">
            <div class="form-group">
                <label for="from">From</label>
                <input type="date" id="from" name="from" value="{{ $fromDate }}" class="form-control">
            </div>
            <div class="form-group">
                <label for="to">To</label>
                <input type="date" id="to" name="to" value="{{ $toDate }}" class="form-control">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>
    
    @if(count($ledger['entries']) > 0)
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Entry ID</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledger['entries'] as $entry)
            <tr>
                <td>{{ $entry['date'] }}</td>
                <td>
                    <a href="{{ route('accounting.journal.show', $entry['journal_entry_id']) }}">
                        #{{ $entry['journal_entry_id'] }}
                    </a>
                </td>
                <td>{{ $entry['description'] }}</td>
                <td class="text-right">
                    {{ $entry['debit'] > 0 ? 'RM ' . number_format($entry['debit'], 2) : '-' }}
                </td>
                <td class="text-right">
                    {{ $entry['credit'] > 0 ? 'RM ' . number_format($entry['credit'], 2) : '-' }}
                </td>
                <td class="text-right {{ $entry['balance'] >= 0 ? 'positive' : 'negative' }}">
                    RM {{ number_format($entry['balance'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total:</th>
                <th class="text-right">RM {{ number_format($ledger['total_debits'], 2) }}</th>
                <th class="text-right">RM {{ number_format($ledger['total_credits'], 2) }}</th>
                <th class="text-right">RM {{ number_format($ledger['total_balance'], 2) }}</th>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="alert alert-info">
        No ledger entries found for this account in the selected date range.
    </div>
    @endif
</div>

@section('styles')
<style>
    .ledger-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .header-actions {
        display: flex;
        gap: 0.5rem;
    }
    .account-type {
        color: #718096;
        margin-bottom: 1.5rem;
    }
    .date-filter {
        background: #f7fafc;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .form-row {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
    }
    .form-group {
        flex: 1;
    }
    .form-control {
        width: 100%;
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
    tfoot tr {
        border-top: 2px solid #e2e8f0;
        background: #f7fafc;
    }
</style>
@endsection
@endsection
