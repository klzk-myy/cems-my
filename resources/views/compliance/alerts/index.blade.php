@extends('layouts.app')

@section('title', 'Alert Triage')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Alert Triage</h1>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm text-red-600">Critical</div>
            <div class="text-2xl font-bold text-red-700">{{ $summary['critical'] }}</div>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="text-sm text-orange-600">High</div>
            <div class="text-2xl font-bold text-orange-700">{{ $summary['high'] }}</div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-sm text-yellow-600">Medium</div>
            <div class="text-2xl font-bold text-yellow-700">{{ $summary['medium'] }}</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-green-600">Low</div>
            <div class="text-2xl font-bold text-green-700">{{ $summary['low'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-semibold">Unassigned Alerts</h2>
            <div class="text-sm text-gray-500">{{ $summary['unassigned'] }} unassigned</div>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Priority</th>
                    <th class="px-4 py-2 text-left text-sm">Customer</th>
                    <th class="px-4 py-2 text-left text-sm">Type</th>
                    <th class="px-4 py-2 text-left text-sm">Risk Score</th>
                    <th class="px-4 py-2 text-left text-sm">Created</th>
                    <th class="px-4 py-2 text-left text-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alerts as $alert)
                <tr class="border-b">
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 rounded text-xs font-medium
                            @if($alert->priority->value === 'critical') bg-red-100 text-red-700
                            @elseif($alert->priority->value === 'high') bg-orange-100 text-orange-700
                            @elseif($alert->priority->value === 'medium') bg-yellow-100 text-yellow-700
                            @else bg-green-100 text-green-700
                            @endif">
                            {{ $alert->priority->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-2">{{ $alert->customer?->full_name ?? 'N/A' }}</td>
                    <td class="px-4 py-2">{{ $alert->type?->value ?? 'N/A' }}</td>
                    <td class="px-4 py-2">{{ $alert->risk_score }}</td>
                    <td class="px-4 py-2">{{ $alert->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('compliance.alerts.show', $alert->id) }}" class="text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No unassigned alerts</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $alerts->links() }}
        </div>
    </div>
</div>
@endsection