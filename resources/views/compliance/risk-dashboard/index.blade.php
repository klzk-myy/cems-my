@extends('layouts.app')

@section('title', 'Risk Dashboard')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Customer Risk Dashboard</h1>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm text-red-600">Critical Risk</div>
            <div class="text-2xl font-bold text-red-700">{{ $summary['critical_risk'] }}</div>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="text-sm text-orange-600">High Risk</div>
            <div class="text-2xl font-bold text-orange-700">{{ $summary['high_risk'] }}</div>
        </div>
        <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
            <div class="text-sm text-gray-600">Medium Risk</div>
            <div class="text-2xl font-bold text-gray-700">{{ $summary['medium_risk'] }}</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-green-600">Deteriorating</div>
            <div class="text-2xl font-bold text-green-700">{{ $summary['deteriorating_trend'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Customer</th>
                    <th class="px-4 py-2 text-left text-sm">Overall Score</th>
                    <th class="px-4 py-2 text-left text-sm">Trend</th>
                    <th class="px-4 py-2 text-left text-sm">Factors</th>
                    <th class="px-4 py-2 text-left text-sm">Last Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                @php $snapshot = $customer->latestRiskSnapshot; @endphp
                <tr class="border-b">
                    <td class="px-4 py-2">
                        <a href="{{ route('compliance.risk-dashboard.customer', $customer->id) }}" class="text-blue-600 hover:underline">
                            {{ $customer->full_name }}
                        </a>
                    </td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 rounded text-xs font-medium
                            @if($snapshot && $snapshot->overall_score >= 80) bg-red-100 text-red-700
                            @elseif($snapshot && $snapshot->overall_score >= 60) bg-orange-100 text-orange-700
                            @else bg-gray-100 text-gray-700
                            @endif">
                            {{ $snapshot?->overall_score ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="px-4 py-2">{{ $snapshot?->trend?->label() ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm">
                        @if($snapshot && isset($snapshot->factors))
                            @foreach(array_slice($snapshot->factors, 0, 2) as $factor)
                                <span class="inline-block bg-gray-100 rounded px-1 mr-1 text-xs">{{ $factor }}</span>
                            @endforeach
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $snapshot?->snapshot_date?->format('Y-m-d') ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No high-risk customers found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $customers->links() }}
        </div>
    </div>
</div>
@endsection