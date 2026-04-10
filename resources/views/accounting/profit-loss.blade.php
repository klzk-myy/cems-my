@extends('layouts.app')

@section('title', 'Profit & Loss Statement - CEMS-MY')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Profit & Loss Statement</h2>
    <form method="GET" class="flex items-center gap-2">
        <label for="from" class="text-sm text-gray-600">From:</label>
        <input type="date" id="from" name="from" value="{{ $fromDate }}" class="p-2 border border-gray-200 rounded text-sm">
        <label for="to" class="text-sm text-gray-600 mx-2">To:</label>
        <input type="date" id="to" name="to" value="{{ $toDate }}" class="p-2 border border-gray-200 rounded text-sm">
        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded text-sm font-semibold hover:bg-gray-700 transition-colors">Update</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="text-center pb-6 mb-6 border-b-2 border-gray-200">
        <h3 class="text-2xl font-bold text-blue-900 m-0">PROFIT AND LOSS STATEMENT</h3>
        <p class="text-gray-500 my-2">Period: {{ $fromDate }} to {{ $toDate }}</p>
        <p class="text-gray-400 text-sm m-0">Generated: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <!-- Revenue Section -->
    <div class="mb-6 pb-6 border-b border-gray-200">
        <h4 class="text-sm font-semibold text-blue-900 uppercase mb-4">REVENUE</h4>
        @if(count($pl['revenues']) > 0)
            @foreach($pl['revenues'] as $account)
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-600">{{ $account['account_name'] }}</span>
                <span class="font-mono text-sm text-green-600">RM {{ number_format((float) $account['amount'], 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-500">No revenue accounts with activity</span>
                <span class="text-gray-400">-</span>
            </div>
        @endif
        <div class="flex justify-between py-3 mt-2 border-t border-gray-200 font-semibold">
            <span class="text-gray-800">Total Revenue</span>
            <span class="font-mono text-green-600">RM {{ number_format($pl['total_revenue'], 2) }}</span>
        </div>
    </div>

    <!-- Expenses Section -->
    <div class="mb-6 pb-6 border-b border-gray-200">
        <h4 class="text-sm font-semibold text-blue-900 uppercase mb-4">EXPENSES</h4>
        @if(count($pl['expenses']) > 0)
            @foreach($pl['expenses'] as $account)
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-600">{{ $account['account_name'] }}</span>
                <span class="font-mono text-sm text-red-600">RM {{ number_format(abs((float) $account['amount']), 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-500">No expense accounts with activity</span>
                <span class="text-gray-400">-</span>
            </div>
        @endif
        <div class="flex justify-between py-3 mt-2 border-t border-gray-200 font-semibold">
            <span class="text-gray-800">Total Expenses</span>
            <span class="font-mono text-red-600">RM {{ number_format(abs($pl['total_expenses']), 2) }}</span>
        </div>
    </div>

    <!-- Net Profit/Loss -->
    <div class="flex justify-between items-center p-6 rounded-lg mt-6 {{ (float) $pl['net_profit'] >= 0 ? 'bg-green-100' : 'bg-red-100' }}">
        <span class="text-xl font-bold {{ (float) $pl['net_profit'] >= 0 ? 'text-green-800' : 'text-red-800' }}">
            NET {{ (float) $pl['net_profit'] >= 0 ? 'PROFIT' : 'LOSS' }}
        </span>
        <span class="text-2xl font-bold font-mono {{ (float) $pl['net_profit'] >= 0 ? 'text-green-800' : 'text-red-800' }}">
            RM {{ number_format(abs((float) $pl['net_profit']), 2) }}
        </span>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white rounded-lg p-6 shadow-sm text-center">
        <h3 class="text-sm text-gray-500 mb-2">Total Revenue</h3>
        <p class="text-2xl font-bold text-green-600">RM {{ number_format((float) $pl['total_revenue'], 2) }}</p>
    </div>
    <div class="bg-white rounded-lg p-6 shadow-sm text-center">
        <h3 class="text-sm text-gray-500 mb-2">Total Expenses</h3>
        <p class="text-2xl font-bold text-red-600">RM {{ number_format(abs((float) $pl['total_expenses']), 2) }}</p>
    </div>
    <div class="bg-white rounded-lg p-6 shadow-sm text-center {{ (float) $pl['net_profit'] >= 0 ? 'bg-green-100' : 'bg-red-100' }}">
        <h3 class="text-sm text-gray-500 mb-2">Net {{ (float) $pl['net_profit'] >= 0 ? 'Profit' : 'Loss' }}</h3>
        <p class="text-2xl font-bold {{ (float) $pl['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
            RM {{ number_format(abs((float) $pl['net_profit']), 2) }}
        </p>
    </div>
</div>
@endsection
