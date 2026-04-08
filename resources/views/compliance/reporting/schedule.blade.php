@extends('layouts.app')

@section('title', 'Report Schedules')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Report Schedules</h1>

    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Create New Schedule</h2>
        </div>
        <form action="{{ route('compliance.reporting.schedule.create') }}" method="POST" class="p-4">
            @csrf
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select name="report_type" class="w-full border rounded px-3 py-2" required>
                        @foreach($reportTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cron Expression</label>
                    <select name="cron_expression" class="w-full border rounded px-3 py-2" required>
                        <option value="0 0 * * *">Daily at midnight</option>
                        <option value="0 0 1 * *">Monthly (1st day)</option>
                        <option value="0 0 * * 1">Weekly (Monday)</option>
                        <option value="0 0 1 1,4,7,10 *">Quarterly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notifications (emails)</label>
                    <input type="text" name="notification_recipients" placeholder="email1,email2" class="w-full border rounded px-3 py-2">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create Schedule</button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Report Type</th>
                    <th class="px-4 py-2 text-left text-sm">Schedule</th>
                    <th class="px-4 py-2 text-left text-sm">Last Run</th>
                    <th class="px-4 py-2 text-left text-sm">Next Run</th>
                    <th class="px-4 py-2 text-left text-sm">Status</th>
                    <th class="px-4 py-2 text-left text-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ strtoupper($schedule->report_type) }}</td>
                    <td class="px-4 py-2">{{ $schedule->getFriendlySchedule() }}</td>
                    <td class="px-4 py-2">{{ $schedule->last_run_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                    <td class="px-4 py-2">{{ $schedule->next_run_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                    <td class="px-4 py-2">
                        @if($schedule->is_active)
                            <span class="text-green-600">Active</span>
                        @else
                            <span class="text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        <form action="{{ route('compliance.reporting.schedule.delete', $schedule->id) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No schedules created yet</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $schedules->links() }}
        </div>
    </div>
</div>
@endsection