@extends('layouts.app')

@section('title', 'Compliance Deadlines')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Compliance Deadlines Calendar</h1>

    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Type</th>
                    <th class="px-4 py-2 text-left text-sm">Reference</th>
                    <th class="px-4 py-2 text-left text-sm">Deadline</th>
                    <th class="px-4 py-2 text-left text-sm">Urgency</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deadlines as $deadline)
                <tr class="border-b">
                    <td class="px-4 py-2">
                        @if($deadline['type'] === 'str')
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">STR</span>
                        @else
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">{{ strtoupper($deadline['report_type'] ?? 'Report') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $deadline['reference'] }}</td>
                    <td class="px-4 py-2">{{ $deadline['deadline'] }}</td>
                    <td class="px-4 py-2">
                        @if($deadline['urgency'] === 'overdue')
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Overdue</span>
                        @elseif($deadline['urgency'] === 'critical')
                            <span class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs">Critical</span>
                        @elseif($deadline['urgency'] === 'warning')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">Warning</span>
                        @else
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Normal</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">No deadlines</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection