@extends('layouts.base')

@section('title', 'POS Dashboard')

@section('header-title')
<div>
    <h1 class="text-2xl font-semibold text-[--color-ink]">Point of Sale</h1>
    <p class="text-sm text-[--color-ink-muted]">{{ now()->format('l, d M Y') }}</p>
</div>
@endsection

@section('header-actions')
<a href="/pos/transactions/create" class="btn btn-primary">New Transaction</a>
@endsection

@section('content')
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <p class="text-sm text-[--color-ink-muted]">Today's Transactions</p>
            <p class="text-2xl font-bold">{{ $todayTransactions }}</p>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <p class="text-sm text-[--color-ink-muted]">Today's Volume</p>
            <p class="text-2xl font-bold">RM {{ number_format($todayVolume, 2) }}</p>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <p class="text-sm text-[--color-ink-muted]">Currencies Tracked</p>
            <p class="text-2xl font-bold">{{ $currencyCount }}</p>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <p class="text-sm text-[--color-ink-muted]">Low Stock Alerts</p>
            <p class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-red-600' : '' }}">{{ $lowStockCount }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header flex justify-between items-center">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 gap-4">
                <a href="/pos/transactions/create" class="btn btn-primary btn-lg w-full justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    New Transaction
                </a>
                <a href="/pos/rates" class="btn btn-secondary btn-lg w-full justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Manage Rates
                </a>
                <a href="/pos/inventory" class="btn btn-secondary btn-lg w-full justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    View Inventory
                </a>
                <a href="/pos/transactions" class="btn btn-secondary btn-lg w-full justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Transaction History
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex justify-between items-center">
            <h3 class="card-title">Today's Rates</h3>
            <a href="/pos/rates" class="btn btn-ghost btn-sm">Edit</a>
        </div>
        <div class="card-body">
            @if($todayRates && count($todayRates) > 0)
                <div class="space-y-2">
                    @foreach($todayRates as $currency => $rate)
                    <div class="flex justify-between items-center py-2 border-b border-[--color-border] last:border-0">
                        <span class="font-medium">{{ $currency }}</span>
                        <div class="flex gap-4 text-sm">
                            <span>Buy: <span class="font-mono">{{ $rate['buy'] ?? '-' }}</span></span>
                            <span>Sell: <span class="font-mono">{{ $rate['sell'] ?? '-' }}</span></span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-[--color-ink-muted] text-center py-4">No rates set for today</p>
                <a href="/pos/rates" class="btn btn-primary w-full">Set Today's Rates</a>
            @endif
        </div>
    </div>
</div>
@endsection
