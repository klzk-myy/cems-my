@extends('layouts.app')

@section('title', 'Reconciliation Report - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Bank Reconciliation Report</h2>
    <p>Account: {{ $report['account_code'] }}</p>
    <p>Period: {{ $report['period']['from'] }} to {{ $report['period']['to'] }}</p>
</div>

<div class="card">
    <h2>Summary</h2>
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;">
        <div class="summary-box">
            <div class="summary-value">{{ number_format($report['statement_balance'] ?? 0, 2) }}</div>
            <div class="summary-label">Statement Balance</div>
        </div>
        <div class="summary-box">
            <div class="summary-value">{{ $report['unmatched_count'] ?? 0 }}</div>
            <div class="summary-label">Unmatched Items</div>
        </div>
        <div class="summary-box">
            <div class="summary-value">{{ $report['exception_count'] ?? 0 }}</div>
            <div class="summary-label">Exceptions</div>
        </div>
    </div>
</div>

@if(isset($report['unmatched_items']) && count($report['unmatched_items']) > 0)
<div class="card">
    <h2>Unmatched Items</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th style="text-align: right;">Debit</th>
                <th style="text-align: right;">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['unmatched_items'] as $item)
            <tr>
                <td>{{ $item->statement_date }}</td>
                <td>{{ $item->reference }}</td>
                <td>{{ $item->description }}</td>
                <td style="text-align: right;">{{ number_format((float) $item->debit, 2) }}</td>
                <td style="text-align: right;">{{ number_format((float) $item->credit, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(isset($report['exceptions']) && count($report['exceptions']) > 0)
<div class="card">
    <h2>Exceptions</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['exceptions'] as $item)
            <tr>
                <td>{{ $item->statement_date }}</td>
                <td>{{ $item->reference }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->notes }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card">
    <h2>statement_balance</h2>
    <p>This report was generated on {{ now()->toDateTimeString() }}</p>
</div>
@endsection