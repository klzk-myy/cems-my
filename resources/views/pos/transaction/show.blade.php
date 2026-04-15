@extends('layouts.base')

@section('title', 'Transaction Details')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Transaction #{{ $transaction->id }}</h5>
            <div>
                <a href="/pos/receipts/{{ $transaction->id }}/thermal" class="btn btn-outline-primary btn-sm" target="_blank">
                    <i class="fas fa-print"></i> Print Receipt
                </a>
                <a href="/pos/receipts/{{ $transaction->id }}/pdf" class="btn btn-outline-secondary btn-sm" target="_blank">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
                <a href="/pos/transactions" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Transaction Information</h6>
                    <table class="table table-sm">
                        <tr><th>Type:</th><td><span class="badge bg-{{ $transaction->type === 'Buy' ? 'success' : 'info' }}">{{ $transaction->type }}</span></td></tr>
                        <tr><th>Currency:</th><td>{{ $transaction->currency_code }}</td></tr>
                        <tr><th>Foreign Amount:</th><td>{{ number_format($transaction->amount_foreign, 2) }} {{ $transaction->currency_code }}</td></tr>
                        <tr><th>Exchange Rate:</th><td>{{ number_format($transaction->rate, 6) }}</td></tr>
                        <tr><th>Local Amount:</th><td><strong>RM {{ number_format($transaction->amount_local, 2) }}</strong></td></tr>
                        <tr><th>Status:</th><td><span class="badge bg-{{ $transaction->status === 'Completed' ? 'success' : 'warning' }}">{{ $transaction->status }}</span></td></tr>
                        <tr><th>CDD Level:</th><td>{{ $transaction->cdd_level ?? 'N/A' }}</td></tr>
                        <tr><th>Purpose:</th><td>{{ $transaction->purpose ?? 'N/A' }}</td></tr>
                        <tr><th>Source of Funds:</th><td>{{ $transaction->source_of_funds ?? 'N/A' }}</td></tr>
                        <tr><th>Created:</th><td>{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Customer Information</h6>
                    <table class="table table-sm">
                        @if($transaction->customer)
                        <tr><th>Name:</th><td>{{ $transaction->customer->name }}</td></tr>
                        <tr><th>ID:</th><td>{{ $transaction->customer->id_number_masked ?? 'N/A' }}</td></tr>
                        <tr><th>Risk Rating:</th><td>
                            <span class="badge bg-{{ $transaction->customer->risk_rating === 'High' ? 'danger' : ($transaction->customer->risk_rating === 'Medium' ? 'warning' : 'success') }}">
                                {{ $transaction->customer->risk_rating ?? 'N/A' }}
                            </span>
                        </td></tr>
                        <tr><th>PEP Status:</th><td>{{ $transaction->customer->is_pep ? 'Yes' : 'No' }}</td></tr>
                        @else
                        <tr><td colspan="2">No customer information</td></tr>
                        @endif
                    </table>
                    <h6 class="mt-3">Counter Information</h6>
                    <table class="table table-sm">
                        <tr><th>Counter:</th><td>{{ $transaction->counter->name ?? 'N/A' }} ({{ $transaction->counter->code ?? 'N/A' }})</td></tr>
                        <tr><th>Processed By:</th><td>{{ $transaction->createdBy->name ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
