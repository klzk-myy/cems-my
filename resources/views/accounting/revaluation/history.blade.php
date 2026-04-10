@extends('layouts.app')

@section('title', 'Revaluation History - CEMS-MY')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Revaluation History</h2>
    <a href="{{ route('accounting.revaluation') }}" class="px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold text-sm hover:bg-gray-300 transition-colors">Back to Revaluation</a>
</div>

<!-- Month Filter -->
<form method="GET" class="flex gap-4 items-center mb-6 bg-white rounded-lg shadow-sm p-4">
    <label for="month" class="text-sm font-semibold text-gray-600">Select Month:</label>
    <input type="month" id="month" name="month" value="{{ $month }}" class="p-2 border border-gray-200 rounded text-sm">
    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-semibold hover:bg-blue-700 transition-colors">Filter</button>
</form>

<!-- Revaluation Entries -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Revaluation Entries for {{ $month }}</h2>

    @if($history->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">ID</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Currency</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Till</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Old Rate</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">New Rate</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Position Amount</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Gain/Loss</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Posted By</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Posted At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history as $entry)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ $entry->id }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800">{{ $entry->currency_code }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ $entry->till_id }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ number_format($entry->old_rate, 6) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ number_format($entry->new_rate, 6) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ number_format($entry->position_amount, 4) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-right font-semibold {{ $entry->gain_loss_amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $entry->gain_loss_amount >= 0 ? '+' : '' }}
                        RM {{ number_format($entry->gain_loss_amount, 2) }}
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ $entry->postedBy->username ?? 'System' }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-500 text-sm">{{ $entry->posted_at->format('Y-m-d H:i:s') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $history->links() }}
    </div>
    @else
    <div class="p-4 rounded bg-blue-50 text-blue-800">
        No revaluation entries found for {{ $month }}.
    </div>
    @endif
</div>

<!-- Summary -->
@if($history->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
    <div class="bg-white rounded-lg p-6 shadow-sm text-center">
        <h3 class="text-sm text-gray-500 mb-2">Total Entries</h3>
        <p class="text-2xl font-bold text-gray-800">{{ $history->count() }}</p>
    </div>
    <div class="bg-white rounded-lg p-6 shadow-sm text-center">
        <h3 class="text-sm text-gray-500 mb-2">Currencies Affected</h3>
        <p class="text-2xl font-bold text-gray-800">{{ $history->pluck('currency_code')->unique()->count() }}</p>
    </div>
    <div class="bg-white rounded-lg p-6 shadow-sm text-center">
        <h3 class="text-sm text-gray-500 mb-2">Total Gain</h3>
        <p class="text-2xl font-bold text-green-600">RM {{ number_format($history->where('gain_loss_amount', '>', 0)->sum('gain_loss_amount'), 2) }}</p>
    </div>
    <div class="bg-white rounded-lg p-6 shadow-sm text-center">
        <h3 class="text-sm text-gray-500 mb-2">Total Loss</h3>
        <p class="text-2xl font-bold text-red-600">RM {{ number_format(abs($history->where('gain_loss_amount', '<', 0)->sum('gain_loss_amount')), 2) }}</p>
    </div>
    <div class="bg-white rounded-lg p-6 shadow-sm text-center {{ $history->sum('gain_loss_amount') >= 0 ? 'bg-green-100' : 'bg-red-100' }}">
        <h3 class="text-sm text-gray-500 mb-2">Net P&L</h3>
        <p class="text-2xl font-bold {{ $history->sum('gain_loss_amount') >= 0 ? 'text-green-600' : 'text-red-600' }}">RM {{ number_format($history->sum('gain_loss_amount'), 2) }}</p>
    </div>
</div>
@endif
@endsection
