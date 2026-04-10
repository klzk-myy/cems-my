@extends('layouts.app')

@section('title', 'Balance Sheet - CEMS-MY')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Balance Sheet</h2>
    <form method="GET" class="flex items-center gap-2">
        <label for="as_of" class="text-sm text-gray-600">As of:</label>
        <input type="date" id="as_of" name="as_of" value="{{ $asOfDate }}" class="p-2 border border-gray-200 rounded text-sm">
        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded text-sm font-semibold hover:bg-gray-700 transition-colors">Update</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="text-center pb-6 mb-6 border-b-2 border-gray-200">
        <h3 class="text-2xl font-bold text-blue-900 m-0">BALANCE SHEET</h3>
        <p class="text-gray-500 my-2">As of: {{ $asOfDate }}</p>
        <p class="text-gray-400 text-sm m-0">Generated: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <!-- Assets Section -->
    <div class="mb-6 pb-6 border-b border-gray-200">
        <h4 class="text-sm font-semibold text-blue-900 uppercase mb-4">ASSETS</h4>
        @if(count($balanceSheet['assets']) > 0)
            @foreach($balanceSheet['assets'] as $account)
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-600">{{ $account['account_name'] }}</span>
                <span class="font-mono text-sm text-gray-800">RM {{ number_format($account['balance'], 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-500">No asset accounts with balance</span>
                <span class="text-gray-400">-</span>
            </div>
        @endif
        <div class="flex justify-between py-3 mt-2 border-t border-gray-200 font-semibold">
            <span class="text-gray-800">Total Assets</span>
            <span class="font-mono text-gray-800">RM {{ number_format($balanceSheet['total_assets'], 2) }}</span>
        </div>
    </div>

    <!-- Liabilities Section -->
    <div class="mb-6 pb-6 border-b border-gray-200">
        <h4 class="text-sm font-semibold text-blue-900 uppercase mb-4">LIABILITIES</h4>
        @if(count($balanceSheet['liabilities']) > 0)
            @foreach($balanceSheet['liabilities'] as $account)
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-600">{{ $account['account_name'] }}</span>
                <span class="font-mono text-sm text-gray-800">RM {{ number_format($account['balance'], 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-500">No liability accounts with balance</span>
                <span class="text-gray-400">-</span>
            </div>
        @endif
        <div class="flex justify-between py-3 mt-2 border-t border-gray-200 font-semibold">
            <span class="text-gray-800">Total Liabilities</span>
            <span class="font-mono text-gray-800">RM {{ number_format($balanceSheet['total_liabilities'], 2) }}</span>
        </div>
    </div>

    <!-- Equity Section -->
    <div class="mb-6 pb-6 border-b border-gray-200">
        <h4 class="text-sm font-semibold text-blue-900 uppercase mb-4">EQUITY</h4>
        @if(count($balanceSheet['equity']) > 0)
            @foreach($balanceSheet['equity'] as $account)
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-600">{{ $account['account_name'] }}</span>
                <span class="font-mono text-sm text-gray-800">RM {{ number_format($account['balance'], 2) }}</span>
            </div>
            @endforeach
        @else
            <div class="flex justify-between py-2 pl-4">
                <span class="text-gray-500">No equity accounts with balance</span>
                <span class="text-gray-400">-</span>
            </div>
        @endif
        <div class="flex justify-between py-3 mt-2 border-t border-gray-200 font-semibold">
            <span class="text-gray-800">Total Equity</span>
            <span class="font-mono text-gray-800">RM {{ number_format($balanceSheet['total_equity'], 2) }}</span>
        </div>
    </div>

    <!-- Balance Check -->
    <div class="p-6 rounded-lg mt-6 {{ $balanceSheet['is_balanced'] ? 'bg-green-100' : 'bg-red-100' }}">
        <div class="flex justify-between py-2">
            <span class="font-medium text-gray-700">Total Assets</span>
            <span class="font-mono font-semibold text-gray-800">RM {{ number_format($balanceSheet['total_assets'], 2) }}</span>
        </div>
        <div class="flex justify-between py-2">
            <span class="font-medium text-gray-700">Total Liabilities + Equity</span>
            <span class="font-mono font-semibold text-gray-800">RM {{ number_format($balanceSheet['liabilities_plus_equity'], 2) }}</span>
        </div>
        <div class="flex justify-between py-3 mt-2 border-t border-gray-300 text-lg">
            <span class="font-semibold text-gray-700">Difference</span>
            <span class="font-mono font-bold {{ $balanceSheet['is_balanced'] ? 'text-green-700' : 'text-red-700' }}">
                @if($balanceSheet['is_balanced'])
                    ✓ Balanced
                @else
                    {{ number_format(abs((float) $balanceSheet['total_assets'] - (float) $balanceSheet['liabilities_plus_equity']), 2) }}
                @endif
            </span>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg p-6 shadow-sm text-center">
        <h3 class="text-sm text-gray-500 mb-2">Total Assets</h3>
        <p class="text-2xl font-bold text-gray-800">RM {{ number_format($balanceSheet['total_assets'], 2) }}</p>
    </div>
    <div class="bg-white rounded-lg p-6 shadow-sm text-center">
        <h3 class="text-sm text-gray-500 mb-2">Total Liabilities</h3>
        <p class="text-2xl font-bold text-gray-800">RM {{ number_format($balanceSheet['total_liabilities'], 2) }}</p>
    </div>
    <div class="bg-white rounded-lg p-6 shadow-sm text-center">
        <h3 class="text-sm text-gray-500 mb-2">Total Equity</h3>
        <p class="text-2xl font-bold text-gray-800">RM {{ number_format($balanceSheet['total_equity'], 2) }}</p>
    </div>
    <div class="bg-white rounded-lg p-6 shadow-sm text-center {{ $balanceSheet['is_balanced'] ? 'bg-green-100' : 'bg-red-100' }}">
        <h3 class="text-sm text-gray-500 mb-2">Balance Status</h3>
        <p class="text-2xl font-bold {{ $balanceSheet['is_balanced'] ? 'text-green-600' : 'text-red-600' }}">
            {{ $balanceSheet['is_balanced'] ? '✓ Balanced' : '✗ Unbalanced' }}
        </p>
    </div>
</div>
@endsection
