@extends('layouts.app')

@section('title', 'STR Studio')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">STR Studio</h1>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-blue-600">Total Pending</div>
            <div class="text-2xl font-bold text-blue-700">{{ $summary['total_pending'] }}</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm text-red-600">Overdue</div>
            <div class="text-2xl font-bold text-red-700">{{ $summary['overdue'] }}</div>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="text-sm text-orange-600">Due in 24h</div>
            <div class="text-2xl font-bold text-orange-700">{{ $summary['due_24h'] }}</div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-sm text-yellow-600">Due in 48h</div>
            <div class="text-2xl font-bold text-yellow-700">{{ $summary['due_48h'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">ID</th>
                    <th class="px-4 py-2 text-left text-sm">Customer</th>
                    <th class="px-4 py-2 text-left text-sm">Filing Deadline</th>
                    <th class="px-4 py-2 text-left text-sm">Confidence</th>
                    <th class="px-4 py-2 text-left text-sm">Status</th>
                    <th class="px-4 py-2 text-left text-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drafts as $draft)
                <tr class="border-b">
                    <td class="px-4 py-2">
                        <a href="{{ route('compliance.str-studio.show', $draft->id) }}" class="text-blue-600 hover:underline">
                            #{{ $draft->id }}
                        </a>
                    </td>
                    <td class="px-4 py-2">{{ $draft->customer?->full_name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 {{ $draft->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                        {{ $draft->filing_deadline?->format('Y-m-d') ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-2">{{ $draft->confidence_score }}%</td>
                    <td class="px-4 py-2">{{ $draft->status->label() }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('compliance.str-studio.show', $draft->id) }}" class="text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No STR drafts</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $drafts->links() }}
        </div>
    </div>
</div>
@endsection