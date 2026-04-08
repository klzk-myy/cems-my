@extends('layouts.app')

@section('title', 'Risk Analysis - ' . $customer->full_name)

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">{{ $customer->full_name }}</h1>
            <p class="text-gray-600">Risk Score Analysis</p>
        </div>
        <a href="{{ route('compliance.risk-dashboard.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Back to Dashboard</a>
    </div>

    @if($trends['current_snapshot'])
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm text-red-600">Overall Score</div>
            <div class="text-3xl font-bold text-red-700">{{ $trends['current_snapshot']->overall_score }}</div>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-blue-600">Velocity</div>
            <div class="text-2xl font-bold text-blue-700">{{ $trends['current_snapshot']->velocity_score }}</div>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="text-sm text-orange-600">Structuring</div>
            <div class="text-2xl font-bold text-orange-700">{{ $trends['current_snapshot']->structuring_score }}</div>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="text-sm text-purple-600">Geographic</div>
            <div class="text-2xl font-bold text-purple-700">{{ $trends['current_snapshot']->geographic_score }}</div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Risk Factors</h2>
        @if($trends['current_snapshot'] && isset($trends['current_snapshot']->factors))
        <div class="flex flex-wrap gap-2">
            @foreach($trends['current_snapshot']->factors as $factor => $weight)
                <span class="px-3 py-1 bg-gray-100 rounded-full text-sm">{{ $factor }}: {{ $weight }}</span>
            @endforeach
        </div>
        @else
        <p class="text-gray-500">No risk factors available</p>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Score History</h2>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Date</th>
                    <th class="px-4 py-2 text-left text-sm">Overall</th>
                    <th class="px-4 py-2 text-left text-sm">Velocity</th>
                    <th class="px-4 py-2 text-left text-sm">Structuring</th>
                    <th class="px-4 py-2 text-left text-sm">Geographic</th>
                    <th class="px-4 py-2 text-left text-sm">Trend</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trends['history'] as $snapshot)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $snapshot->snapshot_date?->format('Y-m-d') ?? 'N/A' }}</td>
                    <td class="px-4 py-2">{{ $snapshot->overall_score }}</td>
                    <td class="px-4 py-2">{{ $snapshot->velocity_score }}</td>
                    <td class="px-4 py-2">{{ $snapshot->structuring_score }}</td>
                    <td class="px-4 py-2">{{ $snapshot->geographic_score }}</td>
                    <td class="px-4 py-2">{{ $snapshot->trend?->label() ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No history available</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
