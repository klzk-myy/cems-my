@extends('layouts.base')

@section('title', 'Transaction Details')

@section('header-title')
<div>
    <h1 class="text-2xl font-semibold text-[--color-ink]">Transaction #{{ $transaction->id }}</h1>
    <p class="text-sm text-[--color-ink-muted]">{{ $transaction->created_at->format('d M Y H:i:s') }}</p>
</div>
@endsection

@section('header-actions')
<div class="flex gap-2">
    <a href="/transactions/{{ $transaction->id }}/receipt" class="btn btn-ghost" target="_blank">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
        </svg>
        Print Receipt
    </a>
    <a href="/pos/transactions" class="btn btn-ghost">Back</a>
</div>
@endsection

@section('content')
<div class="grid grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Transaction Information</h3>
        </div>
        <div class="card-body">
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-[--color-ink-muted]">Type</span>
                    @if($transaction->type === 'Buy')
                        <span class="badge badge-success">Buy</span>
                    @else
                        <span class="badge badge-info">Sell</span>
                    @endif
                </div>
                <div class="flex justify-between">
                    <span class="text-[--color-ink-muted]">Currency</span>
                    <span class="font-medium">{{ $transaction->currency_code }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[--color-ink-muted]">Foreign Amount</span>
                    <span class="font-mono">{{ number_format($transaction->amount_foreign, 2) }} {{ $transaction->currency_code }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[--color-ink-muted]">Exchange Rate</span>
                    <span class="font-mono">{{ number_format($transaction->rate, 6) }}</span>
                </div>
                <div class="flex justify-between border-t border-[--color-border] pt-3">
                    <span class="text-[--color-ink-muted]">Local Amount</span>
                    <span class="text-lg font-bold">RM {{ number_format($transaction->amount_local, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[--color-ink-muted]">Status</span>
                    @if($transaction->status === 'Completed')
                        <span class="badge badge-success">Completed</span>
                    @else
                        <span class="badge badge-warning">{{ $transaction->status }}</span>
                    @endif
                </div>
                <div class="flex justify-between">
                    <span class="text-[--color-ink-muted]">CDD Level</span>
                    <span class="font-medium">{{ $transaction->cdd_level ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[--color-ink-muted]">Purpose</span>
                    <span class="font-medium">{{ $transaction->purpose ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[--color-ink-muted]">Source of Funds</span>
                    <span class="font-medium">{{ $transaction->source_of_funds ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Customer Information</h3>
            </div>
            <div class="card-body">
                @if($transaction->customer)
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-[--color-ink-muted]">Name</span>
                            <span class="font-medium">{{ $transaction->customer->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[--color-ink-muted]">ID</span>
                            <span class="font-mono text-sm">{{ $transaction->customer->id_number_masked ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[--color-ink-muted]">Risk Rating</span>
                            @if($transaction->customer->risk_rating === 'High')
                                <span class="badge badge-danger">High</span>
                            @elseif($transaction->customer->risk_rating === 'Medium')
                                <span class="badge badge-warning">Medium</span>
                            @else
                                <span class="badge badge-success">Low</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[--color-ink-muted]">PEP Status</span>
                            <span class="font-medium">{{ $transaction->customer->is_pep ? 'Yes' : 'No' }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-[--color-ink-muted] text-center py-4">No customer information</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Counter Information</h3>
            </div>
            <div class="card-body">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-[--color-ink-muted]">Counter</span>
                        <span class="font-medium">{{ $transaction->counter->name ?? 'N/A' }} ({{ $transaction->counter->code ?? 'N/A' }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[--color-ink-muted]">Processed By</span>
                        <span class="font-medium">{{ $transaction->createdBy->name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
