@extends('layouts.base')

@section('title', 'Transaction Confirmation')

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Confirm Transaction</h3></div>
    <div class="card-body">
        <div class="bg-[--color-surface-elevated] p-6 rounded-lg mb-6">
            <h4 class="text-sm font-medium text-[--color-ink-muted] mb-4">Transaction Details</h4>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-[--color-ink-muted]">Customer</dt>
                    <dd class="font-medium">{{ $transaction->customer_name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-[--color-ink-muted]">Type</dt>
                    <dd>
                        <span class="badge @if(($transaction->type ?? '') === 'Buy') badge-success @else badge-warning @endif">
                            {{ $transaction->type ?? 'N/A' }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-[--color-ink-muted]">Currency</dt>
                    <dd class="font-mono">{{ $transaction->currency ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-[--color-ink-muted]">Amount</dt>
                    <dd class="font-mono text-lg">{{ number_format($transaction->amount ?? 0, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-[--color-ink-muted]">Rate</dt>
                    <dd class="font-mono">{{ number_format($transaction->rate ?? 0, 4) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-[--color-ink-muted]">MYR Value</dt>
                    <dd class="font-mono text-lg">RM {{ number_format($transaction->myr_value ?? 0, 2) }}</dd>
                </div>
            </dl>
        </div>

        <form method="POST" action="{{ route('transactions.confirm', $transaction->id ?? 0) }}">
            @csrf
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">Approve</button>
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection