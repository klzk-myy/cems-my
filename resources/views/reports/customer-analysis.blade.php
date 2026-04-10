@extends('layouts.app')

@section('title', 'Customer Analysis - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Customer Analysis</h2>
    <p class="text-gray-500 text-sm">Top customers by transaction volume and activity</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="text-3xl font-bold text-blue-900">{{ $topCustomers->count() }}</div>
        <div class="text-sm text-gray-500 mt-2">Top Customers</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="text-3xl font-bold text-blue-900">RM {{ number_format($topCustomers->sum('total_volume'), 0) }}</div>
        <div class="text-sm text-gray-500 mt-2">Total Volume</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="text-3xl font-bold text-blue-900">{{ number_format($topCustomers->avg('transaction_count'), 1) }}</div>
        <div class="text-sm text-gray-500 mt-2">Avg Transactions/Customer</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="text-3xl font-bold text-blue-900">RM {{ number_format($topCustomers->avg('avg_transaction'), 0) }}</div>
        <div class="text-sm text-gray-500 mt-2">Avg Transaction Size</div>
    </div>
</div>

<!-- Risk Distribution Chart -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Risk Distribution</h3>
    <div class="h-64 flex items-center justify-center">
        @if($riskDistribution->isNotEmpty())
        <table class="w-full">
            <tr>
                @foreach($riskDistribution as $risk)
                <td class="text-center p-4">
                    <div class="text-3xl font-bold" style="color: {{ $risk->risk_rating === 'Low' ? '#38a169' : ($risk->risk_rating === 'Medium' ? '#d69e2e' : '#e53e3e') }}">
                        {{ $risk->count }}
                    </div>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $risk->risk_rating === 'Low' ? 'bg-green-100 text-green-800' : ($risk->risk_rating === 'Medium' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                            {{ $risk->risk_rating }}
                        </span>
                    </div>
                </td>
                @endforeach
            </tr>
        </table>
        @else
        <p class="text-gray-500">No risk data available</p>
        @endif
    </div>
</div>

<!-- Top Customers Table -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 50 Customers by Transaction Volume</h3>

    <table class="w-full border-collapse">
        <thead>
            <tr>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Rank</th>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Customer</th>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Risk</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Transactions</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Total Volume</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Avg Transaction</th>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">First Transaction</th>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Last Transaction</th>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Activity</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topCustomers as $index => $customer)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 border-b border-gray-100">{{ $index + 1 }}</td>
                <td class="px-4 py-3 border-b border-gray-100">
                    @php
                        $name = $customer['customer']->full_name ?? 'N/A';
                        $masked = strlen($name) > 4 ? substr($name, 0, 2) . str_repeat('*', strlen($name) - 4) . substr($name, -2) : $name;
                    @endphp
                    <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">{{ $masked }}</span>
                    <br>
                    <small class="text-gray-500">ID: {{ $customer['customer']->id }}</small>
                </td>
                <td class="px-4 py-3 border-b border-gray-100">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ ($customer['risk_rating'] ?? 'Low') === 'Low' ? 'bg-green-100 text-green-800' : (($customer['risk_rating'] ?? 'Low') === 'Medium' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                        {{ $customer['risk_rating'] ?? 'Low' }}
                    </span>
                </td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">{{ number_format($customer['transaction_count']) }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">RM {{ number_format($customer['total_volume'], 2) }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">RM {{ number_format($customer['avg_transaction'], 2) }}</td>
                <td class="px-4 py-3 border-b border-gray-100">{{ $customer['first_transaction'] ? date('d/m/Y', strtotime($customer['first_transaction'])) : 'N/A' }}</td>
                <td class="px-4 py-3 border-b border-gray-100">{{ $customer['last_transaction'] ? date('d/m/Y', strtotime($customer['last_transaction'])) : 'N/A' }}</td>
                <td class="px-4 py-3 border-b border-gray-100">
                    @php
                        $daysSince = $customer['last_transaction'] ? now()->diffInDays($customer['last_transaction']) : null;
                        $activityClass = $daysSince === null ? 'gray' : ($daysSince < 30 ? 'green' : ($daysSince < 90 ? 'yellow' : 'red'));
                    @endphp
                    <span class="{{ $activityClass === 'green' ? 'text-green-600' : ($activityClass === 'yellow' ? 'text-orange-500' : ($activityClass === 'red' ? 'text-red-600' : 'text-gray-500')) }} font-semibold">
                        {{ $daysSince === null ? 'Never' : ($daysSince < 30 ? 'Active' : ($daysSince < 90 ? 'Recent' : 'Inactive')) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                    No customer data found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Activity Legend -->
<div class="bg-white rounded-lg shadow-sm p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Activity Status Legend</h3>
    <div class="flex flex-wrap gap-8">
        <div>
            <span class="text-green-600 font-semibold">● Active</span>
            <p class="text-gray-500 text-sm mt-1">Transaction within last 30 days</p>
        </div>
        <div>
            <span class="text-orange-500 font-semibold">● Recent</span>
            <p class="text-gray-500 text-sm mt-1">Transaction within last 90 days</p>
        </div>
        <div>
            <span class="text-red-600 font-semibold">● Inactive</span>
            <p class="text-gray-500 text-sm mt-1">No transaction in 90+ days</p>
        </div>
    </div>
</div>
@endsection
