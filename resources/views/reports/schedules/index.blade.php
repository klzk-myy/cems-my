<x-app-layout title="Report Schedules">
    <div class="space-y-6">
        <x-page-header title="Report Schedules" description="Manage automated report generation schedules">
            <x-slot:actions>
                <x-button href="{{ route('reports.schedules.create') }}" variant="primary">Create Schedule</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card>
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot:thead>
                        <th>Report Type</th>
                        <th>Frequency</th>
                        <th>Next Run</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </x-slot:thead>
                    <x-slot:tbody>
                        @forelse($schedules as $schedule)
                            <tr>
                                <td>{{ $schedule->report_type }}</td>
                                <td>{{ $schedule->frequency }}</td>
                                <td>{{ $schedule->next_run?->format('d M Y H:i') }}</td>
                                <td>
                                    <x-badge variant="{{ $schedule->is_active ? 'success' : 'gray' }}">
                                        {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                    </x-badge>
                                </td>
                                <td class="flex gap-2">
                                    <x-button href="{{ route('reports.schedules.show', $schedule) }}" variant="secondary">View</x-button>
                                    <x-button href="{{ route('reports.schedules.edit', $schedule) }}" variant="info">Edit</x-button>
                                    <form action="{{ route('reports.schedules.destroy', $schedule) }}" method="POST" onsubmit="return confirm('Delete this schedule?')">
                                        @csrf @method('DELETE')
                                        <x-button type="submit" variant="danger">Delete</x-button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-ink-muted py-4">No schedules found.</td></tr>
                        @endforelse
                    </x-slot:tbody>
                </x-table>
            </div>
        </x-card>
    </div>
</x-app-layout>
