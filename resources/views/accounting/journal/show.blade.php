@extends('layouts.app')

@section('title', 'Journal Entry #' . $entry->id . ' - CEMS-MY')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Journal Entry #{{ $entry->id }}</h2>
    <div class="flex gap-2">
        @if($entry->isPosted() && !$entry->isReversed())
            <form method="POST" action="{{ route('accounting.journal.reverse', $entry) }}" onsubmit="return confirm('Are you sure you want to reverse this entry?');">
                @csrf
                <input type="hidden" name="reason" value="Manual reversal">
                <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded font-semibold text-sm hover:bg-orange-600 transition-colors">Reverse Entry</button>
            </form>
        @endif
        <a href="{{ route('accounting.journal') }}" class="px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold text-sm hover:bg-gray-300 transition-colors">Back to Journal</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Entry Details -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Entry Details</h3>
        <table class="w-full">
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium w-2/5">Entry ID</th>
                <td class="py-3 text-gray-800">#{{ $entry->id }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Date</th>
                <td class="py-3 text-gray-800">{{ $entry->entry_date->format('Y-m-d') }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Status</th>
                <td class="py-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                        {{ $entry->status }}
                    </span>
                </td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Description</th>
                <td class="py-3 text-gray-800">{{ $entry->description }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Reference</th>
                <td class="py-3 text-gray-800">{{ $entry->reference_type }} {{ $entry->reference_id ?? 'N/A' }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Posted By</th>
                <td class="py-3 text-gray-800">{{ $entry->postedBy?->username ?? 'Not posted' }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Posted At</th>
                <td class="py-3 text-gray-800">{{ $entry->posted_at?->format('Y-m-d H:i:s') ?? 'Not posted' }}</td>
            </tr>
            @if($entry->isReversed())
                <tr class="border-b border-gray-100">
                    <th class="py-3 pr-4 text-left text-gray-500 font-medium">Reversed By</th>
                    <td class="py-3 text-gray-800">{{ $entry->reversedBy->username ?? 'System' }}</td>
                </tr>
                <tr>
                    <th class="py-3 pr-4 text-left text-gray-500 font-medium">Reversed At</th>
                    <td class="py-3 text-gray-800">{{ $entry->reversed_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @endif
        </table>
    </div>

    <!-- Balance Summary -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Balance Summary</h3>
        <table class="w-full">
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Total Debits</th>
                <td class="py-3 text-right font-mono text-gray-800">RM {{ number_format($entry->getTotalDebits(), 2) }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Total Credits</th>
                <td class="py-3 text-right font-mono text-gray-800">RM {{ number_format($entry->getTotalCredits(), 2) }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Difference</th>
                <td class="py-3 text-right font-mono font-semibold {{ $entry->isBalanced() ? 'text-green-600' : 'text-red-600' }}">
                    RM {{ number_format(abs($entry->getTotalDebits() - $entry->getTotalCredits()), 2) }}
                </td>
            </tr>
            <tr>
                <th class="py-3 pr-4 text-left text-gray-500 font-medium">Status</th>
                <td class="py-3">
                    @if($entry->isBalanced())
                        <span class="text-green-600 font-semibold">✓ Balanced</span>
                    @else
                        <span class="text-red-600 font-semibold">✗ Not Balanced</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- Journal Lines -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Journal Lines</h3>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Account Code</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Account Name</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Description</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Debit</th>
                    <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b-2 border-gray-200">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->lines as $line)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800">{{ $line->account_code }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ $line->account->account_name ?? 'N/A' }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-gray-600">{{ $line->description ?: '-' }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-right font-mono {{ $line->debit > 0 ? 'text-blue-600' : 'text-gray-400' }}">
                        {{ $line->debit > 0 ? 'RM ' . number_format($line->debit, 2) : '-' }}
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 text-right font-mono {{ $line->credit > 0 ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $line->credit > 0 ? 'RM ' . number_format($line->credit, 2) : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 bg-gray-50">
                    <td colspan="3" class="px-4 py-3 text-right font-semibold text-gray-700">Total:</td>
                    <td class="px-4 py-3 text-right font-semibold font-mono text-gray-700">RM {{ number_format($entry->getTotalDebits(), 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold font-mono text-gray-700">RM {{ number_format($entry->getTotalCredits(), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
