<x-app-layout title="Allocations">
    <div class="space-y-6">
        <x-page-header title="Allocations" description="Teller currency allocation management">
            <x-slot:actions>
                <x-button href="{{ route('allocations.index', ['status' => 'active']) }}" variant="secondary">Active</x-button>
                <x-button href="{{ route('allocations.index', ['status' => 'pending']) }}" variant="secondary">Pending</x-button>
                <x-button href="{{ route('allocations.index', ['status' => 'completed']) }}" variant="secondary">Completed</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card>
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot:thead>
                        <th>ID</th>
                        <th>User</th>
                        <th>Currency</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </x-slot:thead>
                    <x-slot:tbody>
                        @forelse($allocations as $allocation)
                            <tr>
                                <td>{{ $allocation->id }}</td>
                                <td>{{ $allocation->user?->username }}</td>
                                <td>{{ $allocation->currency?->code }}</td>
                                <td>{{ number_format((float) $allocation->allocated_amount, 4) }}</td>
                                <td>
                                    <x-badge variant="{{ $allocation->status->value === 'Active' ? 'success' : ($allocation->status->value === 'Pending' ? 'warning' : 'info') }}">
                                        {{ $allocation->status->value }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-button href="{{ route('allocations.show', $allocation->id) }}" variant="secondary">View</x-button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-ink-muted py-4">No allocations found.</td></tr>
                        @endforelse
                    </x-slot:tbody>
                </x-table>
            </div>
            <div class="mt-4">{{ $allocations->links() }}</div>
        </x-card>
    </div>
</x-app-layout>
