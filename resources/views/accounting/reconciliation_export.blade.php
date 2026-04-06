@extends('layouts.app')

@section('title', 'Reconciliation Export - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Bank Reconciliation Export</h2>
    <p>Account: {{ $report['account_code'] }}</p>
    <p>Period: {{ $report['period']['from'] }} to {{ $report['period']['to'] }}</p>
</div>

<div class="card">
    <h2>Export Data</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['unmatched_items'] ?? [] as $item)
            <tr>
                <td>{{ $item->statement_date }}</td>
                <td>{{ $item->reference }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->debit }}</td>
                <td>{{ $item->credit }}</td>
                <td>{{ $item->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection