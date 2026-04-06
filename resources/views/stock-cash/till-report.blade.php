@extends('layouts.app')

@section('title', 'Till Report - ' . $date . ' - CEMS-MY')

@section('content')
<div style="margin-bottom: 1.5rem;">
    <h2>Till Report - {{ $date }}</h2>
    <a href="{{ route('stock-cash.index') }}" class="btn btn-secondary">&larr; Back to Stock & Cash</a>
</div>

<div class="card">
    <h3>Till Balances for {{ $date }}</h3>

    <table>
        <thead>
            <tr>
                <th>Till ID</th>
                <th>Currency</th>
                <th>Opening Balance</th>
                <th>Closing Balance</th>
                <th>Variance</th>
                <th>Status</th>
                <th>Opened By</th>
                <th>Closed By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($balances as $balance)
            <tr>
                <td><strong>{{ $balance->till_id }}</strong></td>
                <td>{{ $balance->currency_code }}</td>
                <td>{{ number_format($balance->opening_balance, 4) }}</td>
                <td>{{ $balance->closing_balance ? number_format($balance->closing_balance, 4) : '-' }}</td>
                <td class="{{ ($balance->variance ?? 0) == 0 ? 'pnl-positive' : (($balance->variance ?? 0) > 0 ? 'pnl-positive' : 'pnl-negative')) }}">
                    {{ $balance->variance ? number_format($balance->variance, 4) : '-' }}
                </td>
                <td>
                    @if($balance->closed_at)
                        <span style="color: #38a169;">Closed</span>
                    @else
                        <span style="color: #e53e3e;">Open</span>
                    @endif
                </td>
                <td>{{ $balance->opener->username ?? 'N/A' }}</td>
                <td>{{ $balance->closer->username ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $totalVariance = $balances->sum('variance');
    @endphp

    @if($balances->count() > 1)
    <div style="margin-top: 1rem; padding: 1rem; background: #f7fafc; border-radius: 4px;">
        <strong>Total Variance: </strong>
        <span class="{{ $totalVariance == 0 ? 'pnl-positive' : 'pnl-negative' }}" style="font-size: 1.25rem;">
            {{ number_format($totalVariance, 4) }}
        </span>
    </div>
    @endif
</div>
@endsection
