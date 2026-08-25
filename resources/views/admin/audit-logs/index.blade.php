<x-app-layout title="Audit Trail">
    <x-page-header title="Audit Trail" description="Immutable hash-chained record of all system activity." />

    @if (isset($unsealedCount) && $unsealedCount > 0)
        <div class="mb-4 rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
            {{ $unsealedCount }} entries awaiting async sealing. Run <code>php artisan audit:verify</code> to verify chain integrity.
        </div>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-input name="action" label="Action contains" :value="request('action')" />
        <x-input name="entity_type" label="Entity type" :value="request('entity_type')" />
        <x-select name="severity" label="Severity" :options="['' => 'All', 'INFO' => 'Info', 'WARNING' => 'Warning', 'ERROR' => 'Error', 'CRITICAL' => 'Critical']" :value="request('severity')" />
        <x-button type="submit" variant="secondary">Filter</x-button>
    </form>

    <div class="overflow-x-auto rounded border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-3 py-2">#</th>
                    <th class="px-3 py-2">Time</th>
                    <th class="px-3 py-2">User</th>
                    <th class="px-3 py-2">Action</th>
                    <th class="px-3 py-2">Entity</th>
                    <th class="px-3 py-2">Severity</th>
                    <th class="px-3 py-2">Sealed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-mono">{{ $log->id }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-3 py-2">{{ $log->user_id ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $log->action }}</td>
                        <td class="px-3 py-2">{{ $log->entity_type }}{{ $log->entity_id ? '#'.$log->entity_id : '' }}</td>
                        <td class="px-3 py-2">{{ $log->severity ?? 'INFO' }}</td>
                        <td class="px-3 py-2">{{ $log->entry_hash ? 'Yes' : 'Pending' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">No audit entries match the filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-app-layout>
