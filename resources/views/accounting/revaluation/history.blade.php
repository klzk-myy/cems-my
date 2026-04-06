@extends('layouts.app')

@section('title', 'Revaluation History - CEMS-MY')

@section('content')
<div class="history-header">
    <h2>Revaluation History</h2>
    <div class="header-actions">
        <a href="{{ route('accounting.revaluation') }}" class="btn btn-secondary">Back to Revaluation</a>
    </div>
</div>

<!-- Month Filter -->
<div class="card">
    <form method="GET" class="date-filter">
        <label for="month">Select Month:</label>
        <input type="month" id="month" name="month" value="{{ $month }}" class="form-control">
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<!-- Revaluation Entries -->
<div class="card">
    <h2>Revaluation Entries for {{ $month }}</h2>
    
    @if($history->count() > 0)
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Currency</th>
                <th>Till</th>
                <th>Old Rate</th>
                <th>New Rate</th>
                <th>Position Amount</th>
                <th class="text-right">Gain/Loss</th>
                <th>Posted By</th>
                <th>Posted At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $entry)
            <tr>
                <td>{{ $entry->id }}</td>
                <td><strong>{{ $entry->currency_code }}</strong></td>
                <td>{{ $entry->till_id }}</td>
                <td>{{ number_format($entry->old_rate, 6) }}</td>
                <td>{{ number_format($entry->new_rate, 6) }}</td>
                <td>{{ number_format($entry->position_amount, 4) }}</td>
                <td class="text-right {{ $entry->gain_loss_amount >= 0 ? 'positive' : 'negative' }}">
                    {{ $entry->gain_loss_amount >= 0 ? '+' : '' }}
                    RM {{ number_format($entry->gain_loss_amount, 2) }}
                </td>
                <td>{{ $entry->postedBy->username ?? 'System' }}</td>
                <td>{{ $entry->posted_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 1rem;">
        {{ $history->links() }}
    </div>
    @else
    <div class="alert alert-info">
        No revaluation entries found for {{ $month }}.
    </div>
    @endif
</div>

<!-- Summary -->
@if($history->count() > 0)
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
    <div class="card summary-card">
        <h3>Total Entries</h3>
        <p class="amount">{{ $history->count() }}</p>
    </div>
    <div class="card summary-card">
        <h3>Currencies Affected</h3>
        <p class="amount">{{ $history->pluck('currency_code')->unique()->count() }}</p>
    </div>
    <div class="card summary-card">
        <h3>Total Gain</h3>
        <p class="amount positive">RM {{ number_format($history->where('gain_loss_amount', '>', 0)->sum('gain_loss_amount'), 2) }}</p>
    </div>
    <div class="card summary-card">
        <h3>Total Loss</h3>
        <p class="amount negative">RM {{ number_format(abs($history->where('gain_loss_amount', '<', 0)->sum('gain_loss_amount')), 2) }}</p>
    </div>
    <div class="card summary-card {{ $history->sum('gain_loss_amount') >= 0 ? 'gain-card' : 'loss-card' }}">
        <h3>Net P&L</h3>
        <p class="amount">RM {{ number_format($history->sum('gain_loss_amount'), 2) }}</p>
    </div>
</div>
@endif

@section('styles')
<style>
    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .header-actions {
        display: flex;
        gap: 0.5rem;
    }
    .date-filter {
        display: flex;
        gap: 1rem;
        align-items: center;
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
    .gain-card {
        background: #c6f6d5;
    }
    .loss-card {
        background: #fed7d7;
    }
</style>
@endsection
@endsection
