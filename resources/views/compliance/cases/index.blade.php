@extends('layouts.app')

@section('title', 'Case Management')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Case Management</h1>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="text-sm text-purple-600">Total Open</div>
            <div class="text-2xl font-bold text-purple-700">{{ $summary['total_open'] }}</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm text-red-600">Critical</div>
            <div class="text-2xl font-bold text-red-700">{{ $summary['critical'] }}</div>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="text-sm text-orange-600">High</div>
            <div class="text-2xl font-bold text-orange-700">{{ $summary['high'] }}</div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-sm text-yellow-600">Overdue</div>
            <div class="text-2xl font-bold text-yellow-700">{{ $summary['overdue'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Case #</th>
                    <th class="px-4 py-2 text-left text-sm">Customer</th>
                    <th class="px-4 py-2 text-left text-sm">Status</th>
                    <th class="px-4 py-2 text-left text-sm">Priority</th>
                    <th class="px-4 py-2 text-left text-sm">SLA Deadline</th>
                    <th class="px-4 py-2 text-left text-sm">Alerts</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cases as $case)
                <tr class="border-b">
                    <td class="px-4 py-2">
                        <a href="{{ route('compliance.cases.show', $case->id) }}" class="text-blue-600 hover:underline">
                            {{ $case->case_number }}
                        </a>
                    </td>
                    <td class="px-4 py-2">{{ $case->customer?->full_name ?? 'N/A' }}</td>
                    <td class="px-4 py-2">{{ $case->status->label() }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 rounded text-xs font-medium
                            @if($case->priority->value === 'critical') bg-red-100 text-red-700
                            @elseif($case->priority->value === 'high') bg-orange-100 text-orange-700
                            @else bg-gray-100 text-gray-700
                            @endif">
                            {{ $case->priority->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-2 {{ $case->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                        {{ $case->sla_deadline?->format('Y-m-d H:i') ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-2">{{ $case->alerts->count() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No open cases</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $cases->links() }}
        </div>
    </div>
</div>
@endsection