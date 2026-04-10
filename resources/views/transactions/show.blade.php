@extends('layouts.app')

@section('title', 'Transaction #' . $transaction->id . ' - CEMS-MY')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 mb-1">Transaction #{{ $transaction->id }}</h2>
        <p class="text-sm text-gray">Created {{ $transaction->created_at->diffForHumans() }} by {{ $transaction->user->username ?? 'Unknown' }}</p>
    </div>
    <div>
        <a href="/transactions" class="btn btn-secondary">Back to List</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

@if($transaction->status === 'Pending')
    <div class="compliance-box">
        <h4>Pending Approval</h4>
        <p>This transaction requires manager approval (>= RM 50,000).</p>
        @if(auth()->user()->isManager())
            <form action="/transactions/{{ $transaction->id }}/approve" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="btn btn-success">Approve Transaction</button>
            </form>
        @endif
    </div>
@endif

@if($transaction->status === 'OnHold')
    <div class="alert alert-error">
        <strong>Transaction On Hold:</strong> {{ $transaction->hold_reason }}
    </div>
@endif

<!-- Receipt -->
<div class="card">
    <div class="receipt-box">
        <div class="receipt-title">CEMS-MY</div>
        <div class="receipt-id">Transaction Receipt</div>
        <div class="amount-display">
            @if($transaction->type === 'Buy')
                <span class="text-success font-bold">+</span>
            @else
                <span class="text-danger font-bold">-</span>
            @endif
            {{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }}
        </div>
        <div class="text-gray">
            @ {{ number_format($transaction->rate, 6) }} MYR/{{ $transaction->currency_code }}
        </div>
        <div class="mt-4 pt-4 border-top">
            <div class="text-2xl font-bold">
                RM {{ number_format($transaction->amount_local, 2) }}
            </div>
            <div class="text-gray">Total Amount</div>
        </div>
        <div class="mt-4">
            @php
                $statusClass = match($transaction->status) {
                    'Completed' => 'status-completed',
                    'Pending' => 'status-pending',
                    'OnHold' => 'status-onhold',
                    default => 'status-pending'
                };
            @endphp
            <span class="status-badge {{ $statusClass }}">{{ $transaction->status }}</span>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Transaction Details -->
    <div class="card">
        <h2>Transaction Details</h2>
        <div class="detail-row">
            <span class="detail-label">Transaction ID</span>
            <span class="detail-value">#{{ $transaction->id }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Type</span>
            <span class="detail-value type-buy">{{ $transaction->type->value }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Currency</span>
            <span class="detail-value">{{ $transaction->currency_code }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Foreign Amount</span>
            <span class="detail-value">{{ number_format($transaction->amount_foreign, 4) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Exchange Rate</span>
            <span class="detail-value">{{ number_format($transaction->rate, 6) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Local Amount (MYR)</span>
            <span class="detail-value">RM {{ number_format($transaction->amount_local, 2) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value">
                <span class="status-badge {{ $statusClass }}">{{ $transaction->status }}</span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">CDD Level</span>
            <span class="detail-value">{{ $transaction->cdd_level }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Created At</span>
            <span class="detail-value">{{ $transaction->created_at->format('Y-m-d H:i:s') }}</span>
        </div>
    </div>

    <!-- Customer Details -->
    <div class="card">
        <h2>Customer Information</h2>
        <div class="detail-row">
            <span class="detail-label">Name</span>
            <span class="detail-value">{{ $transaction->customer->full_name ?? 'N/A' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">ID Type</span>
            <span class="detail-value">{{ $transaction->customer->id_type ?? 'N/A' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">ID Number</span>
            <span class="detail-value">{{ $transaction->customer->id_number ?? 'N/A' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Risk Rating</span>
            <span class="detail-value">{{ $transaction->customer->risk_rating ?? 'N/A' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Purpose</span>
            <span class="detail-value">{{ $transaction->purpose }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Source of Funds</span>
            <span class="detail-value">{{ $transaction->source_of_funds }}</span>
        </div>
    </div>
</div>

<!-- Processing Details -->
<div class="card">
    <h2>Processing Details</h2>
    <div class="detail-row">
        <span class="detail-label">Processed By</span>
        <span class="detail-value">{{ $transaction->user->username ?? 'N/A' }}</span>
    </div>
    @if($transaction->approved_by)
        <div class="detail-row">
            <span class="detail-label">Approved By</span>
            <span class="detail-value">{{ $transaction->approver->username ?? 'N/A' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Approved At</span>
            <span class="detail-value">{{ $transaction->approved_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</span>
        </div>
    @endif
    @if($transaction->hold_reason)
        <div class="detail-row">
            <span class="detail-label">Hold Reason</span>
            <span class="detail-value text-danger">{{ $transaction->hold_reason }}</span>
        </div>
    @endif
</div>

<!-- Compliance Flags -->
@if($transaction->flags && $transaction->flags->count() > 0)
    <div class="card">
        <h2>Compliance Flags</h2>
        @foreach($transaction->flags as $flag)
            <div class="alert alert-warning mb-2">
                <strong>{{ $flag->flag_type }}:</strong> {{ $flag->flag_reason }}
            </div>
        @endforeach
    </div>
@endif

<!-- Journal Entries -->
@if($transaction->status === 'Completed')
    <div class="card">
        <h2>Accounting Journal Entries</h2>
        @if($transaction->type === 'Buy')
            <div class="journal-entry">
                <div class="journal-dr">Dr Foreign Currency Inventory (2000)</div>
                <div class="ml-8">RM {{ number_format($transaction->amount_local, 2) }}</div>
            </div>
            <div class="journal-entry">
                <div class="journal-cr">Cr Cash - MYR (1000)</div>
                <div class="ml-8">RM {{ number_format($transaction->amount_local, 2) }}</div>
            </div>
            <div class="mt-4 text-sm text-gray">
                <strong>Narration:</strong> Buy {{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }} @ {{ number_format($transaction->rate, 6) }}
            </div>
        @else
            <div class="journal-entry">
                <div class="journal-dr">Dr Cash - MYR (1000)</div>
                <div class="ml-8">RM {{ number_format($transaction->amount_local, 2) }}</div>
            </div>
            <div class="journal-entry">
                <div class="journal-cr">Cr Foreign Currency Inventory (2000)</div>
                <div class="ml-8">RM {{ number_format($transaction->amount_local, 2) }}</div>
            </div>
            @php
                $position = \App\Models\CurrencyPosition::where('currency_code', $transaction->currency_code)->first();
                $avgCost = $position ? $position->avg_cost_rate : $transaction->rate;
                $costBasis = $transaction->amount_foreign * $avgCost;
                $revenue = $transaction->amount_local - $costBasis;
            @endphp
            @if($revenue >= 0)
                <div class="journal-entry">
                    <div class="journal-cr">Cr Revenue - Forex (4000)</div>
                    <div class="ml-8">RM {{ number_format($revenue, 2) }}</div>
                </div>
            @else
                <div class="journal-entry">
                    <div class="journal-dr">Dr Expense - Forex Loss (5000)</div>
                    <div class="ml-8">RM {{ number_format(abs($revenue), 2) }}</div>
                </div>
            @endif
            <div class="mt-4 text-sm text-gray">
                <strong>Narration:</strong> Sell {{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }} @ {{ number_format($transaction->rate, 6) }}
            </div>
        @endif
    </div>
@endif

<!-- Actions -->
<div class="card text-center">
    <a href="/transactions" class="btn btn-secondary">Back to Transactions</a>
    <a href="/transactions/create" class="btn btn-success">New Transaction</a>
    @if($transaction->status === 'Completed')
    <a href="/transactions/{{ $transaction->id }}/receipt" class="btn btn-primary" target="_blank">
        Print Receipt
    </a>
    @endif
    @if($transaction->status === 'Pending' && auth()->user()->isManager())
    <form action="/transactions/{{ $transaction->id }}/approve" method="POST" class="inline">
        @csrf
        <button type="submit" class="btn btn-success">Approve</button>
    </form>
    @endif
    @if($transaction->isRefundable() && (auth()->user()->isManager() || auth()->user()->isAdmin() || auth()->user()->id === $transaction->user_id))
    <a href="{{ route('transactions.cancel.show', $transaction) }}" class="btn btn-danger">
    Cancel Transaction
    </a>
    @endif
</div>
@endsection
