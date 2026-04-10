@extends('layouts.app')

@section('title', 'Account Ledger - CEMS-MY')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Account Ledger</h2>
    <a href="{{ route('accounting.ledger') }}" class="px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold text-sm hover:bg-gray-300 transition-colors">Back to Trial Balance</a>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $ledger['account']['account_code'] }} - {{ $ledger['account']['account_name'] }}</h3>
    <p class="text-gray-500 mb-6">{{ $ledger['account']['account_type'] }}</p>

    <!-- Date Filter -->
    <form method="GET" class="flex gap-4 items-end p-4 bg-gray-50 rounded-lg mb-6">
        <div class="flex-1">
            <label for="from" class="block mb-1 text-sm font-medium text-gray-600">From</label>
            <input type="date" id="from" name="from" value="{{ $fromDate }}" class="w-full p-2 border border-gray-200 rounded text-sm">
        </div>
        <div class="flex-1">
            <label for="to" class="block mb-1 text-sm font-medium text-gray-600">To</label>
            <input type="date" id="to" name="to" value="{{ $toDate }}" class="w-full p-2 border border-gray-200 rounded text-sm">
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-semibold hover:bg-blue-700 transition-colors">Filter</button>
        </div>
    </form>

    @if(count($ledger['entries']) > 0)
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Date</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Entry ID</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Description</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Debit</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Credit</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ledger['entries'] as $entry)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ $entry['date'] }}</td>
                    <td class="px-4 py-3 border-b border-gray-100">
                        <a href="{{ route('accounting.journal.show', $entry['journal_entry_id']) }}" class="text-blue-600 no-underline hover:underline">
                            #{{ $entry['journal_entry_id'] }}
                        </a>
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ $entry['description'] }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-right text-gray-600">
                        {{ $entry['debit'] > 0 ? 'RM ' . number_format($entry['debit'], 2) : '-' }}
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 text-right text-gray-600">
                        {{ $entry['credit'] > 0 ? 'RM ' . number_format($entry['credit'], 2) : '-' }}
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 text-right font-semibold {{ $entry['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        RM {{ number_format($entry['balance'], 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 bg-gray-50">
                    <td colspan="3" class="px-4 py-3 text-right font-semibold text-gray-700">Total:</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-700">RM {{ number_format($ledger['total_debits'], 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-700">RM {{ number_format($ledger['total_credits'], 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-700">RM {{ number_format($ledger['total_balance'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div class="p-4 rounded bg-blue-50 text-blue-800">
        No ledger entries found for this account in the selected date range.
    </div>
    @endif
</div>
@endsection
