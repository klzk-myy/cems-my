@extends('layouts.app')

@section('title', 'Monthly Revaluation - CEMS-MY')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Monthly Revaluation</h2>
    <div class="flex gap-2">
        <form method="POST" action="{{ route('accounting.revaluation.run') }}" onsubmit="return confirm('Are you sure you want to run revaluation? This will create journal entries.');">
            @csrf
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-semibold text-sm hover:bg-blue-700 transition-colors">Run Revaluation</button>
        </form>
        <a href="{{ route('accounting.revaluation.history') }}" class="px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold text-sm hover:bg-gray-300 transition-colors">View History</a>
    </div>
</div>

@if(session('success'))
    <div class="p-4 mb-4 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="p-4 mb-4 rounded bg-red-100 text-red-800">{{ session('error') }}</div>
@endif

<!-- Revaluation Status -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Current Month Status</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="p-4 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-500 mb-1 block">Month</span>
            <span class="font-semibold text-gray-800 text-lg">{{ $status['month'] }}</span>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-500 mb-1 block">Status</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $status['has_run'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                {{ $status['has_run'] ? 'Completed' : 'Pending' }}
            </span>
        </div>
        @if($status['has_run'])
        <div class="p-4 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-500 mb-1 block">Run Date</span>
            <span class="font-semibold text-gray-800 text-lg">{{ $status['run_date'] }}</span>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-500 mb-1 block">Positions Updated</span>
            <span class="font-semibold text-gray-800 text-lg">{{ $status['positions_updated'] }}</span>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-500 mb-1 block">Total Gain/Loss</span>
            <span class="font-semibold text-lg {{ ($status['total_gain_loss'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                RM {{ number_format($status['total_gain_loss'] ?? 0, 2) }}
            </span>
        </div>
        @endif
    </div>
</div>

<!-- Currency Positions -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-2">Currency Positions</h2>
    <p class="text-gray-500 mb-4">
        Revaluation calculates unrealized P&L using: <strong>(Current Rate - Avg Cost Rate) × Position Amount</strong>
    </p>

    @if($positions->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Currency</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Till</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Balance</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Avg Cost Rate</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Last Valuation</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Unrealized P&L</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Last Updated</th>
                </tr>
            </thead>
            <tbody>
                @foreach($positions as $position)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800">{{ $position->currency_code }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ $position->till_id }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ number_format($position->balance, 4) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ number_format($position->avg_cost_rate, 6) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ $position->last_valuation_rate ? number_format($position->last_valuation_rate, 6) : 'N/A' }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-right font-semibold {{ ($position->unrealized_pnl ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ ($position->unrealized_pnl ?? 0) >= 0 ? '+' : '' }}
                        RM {{ number_format($position->unrealized_pnl ?? 0, 2) }}
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-500 text-sm">{{ $position->updated_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 bg-gray-50">
                    <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-700">Total Unrealized P&L:</td>
                    <td class="px-4 py-3 text-right font-semibold text-lg {{ $positions->sum('unrealized_pnl') >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $positions->sum('unrealized_pnl') >= 0 ? '+' : '' }}
                        RM {{ number_format($positions->sum('unrealized_pnl'), 2) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div class="p-4 rounded bg-blue-50 text-blue-800">
        No currency positions found. Positions are created automatically when transactions are processed.
    </div>
    @endif
</div>

<!-- Revaluation Schedule -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Automation Schedule</h2>
    <div class="p-4 rounded bg-blue-50 text-blue-800">
        <strong>Automatic Revaluation:</strong> Runs on the last day of each month at 23:59<br>
        <strong>Next Run:</strong> {{ now()->endOfMonth()->format('Y-m-d 23:59') }}<br>
        <strong>Notification:</strong> Manager and Compliance Officer will receive email notification
    </div>
</div>
@endsection
