@extends('layouts.app')

@section('title', 'Import Results - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Import Results</h2>
    <p class="text-gray-500 text-sm">Details for import: <strong>{{ $import->original_filename }}</strong></p>
</div>

<!-- Summary Card -->
<div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Import Summary</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="p-4 bg-gray-50 rounded">
            <label class="block text-xs text-gray-500 mb-1">Filename</label>
            <div class="text-sm font-semibold text-gray-800">{{ $import->original_filename }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded">
            <label class="block text-xs text-gray-500 mb-1">Import Date</label>
            <div class="text-sm font-semibold text-gray-800">{{ $import->created_at->format('Y-m-d H:i:s') }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded">
            <label class="block text-xs text-gray-500 mb-1">Total Rows</label>
            <div class="text-2xl font-bold text-gray-800">{{ $import->total_rows }}</div>
        </div>
        <div class="p-4 bg-green-50 rounded">
            <label class="block text-xs text-green-600 mb-1">Success</label>
            <div class="text-2xl font-bold text-green-600">{{ $import->success_count }}</div>
        </div>
        <div class="p-4 bg-red-50 rounded">
            <label class="block text-xs text-red-600 mb-1">Errors</label>
            <div class="text-2xl font-bold text-red-600">{{ $import->error_count }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded">
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <div class="text-sm font-semibold text-gray-800">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-{{ $import->getStatusColor() === 'success' ? 'green-100 text-green-800' : ($import->getStatusColor() === 'warning' ? 'orange-100 text-orange-800' : 'red-100 text-red-800') }}">
                    {{ ucfirst($import->status) }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Successful Transactions -->
@if($import->success_count > 0)
<div class="card">
    <h3>Successful Transactions</h3>
    <p class="text-green-600 mb-4">
        ✓ {{ $import->success_count }} transactions imported successfully
    </p>
    <p class="text-gray-500 text-sm">
        View all transactions in the <a href="{{ route('transactions.index') }}" class="text-blue-600 hover:underline">Transaction History</a>
    </p>
</div>
@endif

<!-- Error Details -->
@if($import->hasErrors())
<div class="mt-8">
    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <h3 class="text-red-800 font-semibold mb-2">⚠️ Error Details ({{ count($import->getErrors()) }} errors)</h3>
        <p class="text-red-700 mb-4">The following rows could not be imported:</p>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="bg-red-100 text-red-800 font-semibold px-3 py-2 text-left">Row</th>
                        <th class="bg-red-100 text-red-800 font-semibold px-3 py-2 text-left">Data</th>
                        <th class="bg-red-100 text-red-800 font-semibold px-3 py-2 text-left">Error Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($import->getErrors() as $error)
                    <tr>
                        <td class="border-b border-red-200 px-3 py-2">{{ $error['row'] }}</td>
                        <td class="border-b border-red-200 px-3 py-2 font-mono text-sm max-w-xs overflow-hidden text-ellipsis whitespace-nowrap" title="{{ implode(', ', $error['data']) }}">
                            {{ implode(', ', $error['data']) }}
                        </td>
                        <td class="border-b border-red-200 px-3 py-2 text-red-600 text-sm">{{ $error['error'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('transactions.batch-upload') }}?export_errors={{ $import->id }}" class="btn btn-secondary">
                Export Errors to CSV
            </a>
        </div>
    </div>
</div>
@endif

<!-- Processing Time -->
@if($import->started_at && $import->completed_at)
<div class="card mt-8">
    <h3>Processing Details</h3>
    <div class="grid grid-cols-3 gap-4 mt-4">
        <div class="p-4 bg-gray-50 rounded">
            <label class="block text-xs text-gray-500 mb-1">Started At</label>
            <div class="text-sm font-semibold text-gray-800">{{ $import->started_at->format('Y-m-d H:i:s') }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded">
            <label class="block text-xs text-gray-500 mb-1">Completed At</label>
            <div class="text-sm font-semibold text-gray-800">{{ $import->completed_at->format('Y-m-d H:i:s') }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded">
            <label class="block text-xs text-gray-500 mb-1">Duration</label>
            <div class="text-sm font-semibold text-gray-800">{{ $import->started_at->diffInSeconds($import->completed_at) }} seconds</div>
        </div>
    </div>
</div>
@endif

<div class="flex gap-4 mt-8">
    <a href="{{ route('transactions.batch-upload') }}" class="btn btn-secondary">
        ← Back to Upload
    </a>
    <a href="{{ route('transactions.index') }}" class="btn">
        View All Transactions
    </a>
</div>
@endsection
