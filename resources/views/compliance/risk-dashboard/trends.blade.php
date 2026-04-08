@extends('layouts.app')

@section('title', 'Risk Trends')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Customer Risk Trends</h1>
        <a href="{{ route('compliance.risk-dashboard.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Back to Dashboard</a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Customers Needing Rescreening</h2>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Customer</th>
                    <th class="px-4 py-2 text-left text-sm">Current Score</th>
                    <th class="px-4 py-2 text-left text-sm">Last Screened</th>
                    <th class="px-4 py-2 text-left text-sm">Days Overdue</th>
                    <th class="px-4 py-2 text-left text-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($needsRescreening as $customer)
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
                    <td class="px-4 py-2">{{ $snapshot?->snapshot_date?->format('Y-m-d') ?? 'Never' }}</td>
                    <td class="px-4 py-2 text-red-600 font-medium">
                        @if($snapshot?->next_screening_date)
                            {{ now()->diffInDays($snapshot->next_screening_date) }} days
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        <form action="{{ route('compliance.risk-dashboard.rescreen') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                            <button type="submit" class="text-blue-600 hover:underline">Rescreen</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">All customers are up to date</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
