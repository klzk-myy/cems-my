<x-app-layout title="Stock Transfer #{{ $stockTransfer->id }}">
    <div class="space-y-6">
        <x-page-header
            title="Stock Transfer #{{ $stockTransfer->id }}"
            :description="($stockTransfer->source_branch_name ?? 'N/A') . ' → ' . ($stockTransfer->destination_branch_name ?? 'N/A')"
            :actions="true"
        >
            <x-slot:actions>
                <x-button href="{{ route('stock-transfers.index') }}" variant="secondary">Back</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card title="Transfer Details">
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-ink-muted">Transfer Number</dt>
                        <dd class="mt-1 text-sm text-ink">#{{ $stockTransfer->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-muted">Status</dt>
                        <dd class="mt-1 text-sm">
                            <x-badge variant="{{ $stockTransfer->status->value === 'Completed' ? 'success' : ($stockTransfer->status->value === 'Requested' ? 'warning' : ($stockTransfer->status->value === 'Cancelled' ? 'danger' : 'info')) }}">
                                {{ $stockTransfer->status->label() }}
                            </x-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-muted">Source Branch</dt>
                        <dd class="mt-1 text-sm text-ink">
                            {{ $stockTransfer->sourceBranch->code ?? 'N/A' }} - {{ $stockTransfer->sourceBranch->name ?? 'N/A' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-muted">Destination Branch</dt>
                        <dd class="mt-1 text-sm text-ink">
                            {{ $stockTransfer->destinationBranch->code ?? 'N/A' }} - {{ $stockTransfer->destinationBranch->name ?? 'N/A' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-muted">Requested By</dt>
                        <dd class="mt-1 text-sm text-ink">
                            {{ $stockTransfer->requestedBy?->username ?? 'N/A' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-muted">Request Date</dt>
                        <dd class="mt-1 text-sm text-ink">
                            {{ $stockTransfer->created_at->format('d M Y H:i:s') }}
                        </dd>
                    </div>
                </div>

                @if($stockTransfer->notes)
                    <div class="mt-6 pt-6 border-t border-border">
                        <dt class="text-sm font-medium text-ink-muted mb-2">Notes</dt>
                        <dd class="text-sm text-ink-muted">{{ $stockTransfer->notes }}</dd>
                    </div>
                @endif
            </div>
        </x-card>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-card title="Branch Manager Approval">
                <div class="space-y-3">
                    @if($stockTransfer->branchManagerApprovedBy)
                        <div class="flex items-center gap-2 text-success-text">
                            <x-badge variant="success">Approved</x-badge>
                        </div>
                        <p class="text-sm text-ink-muted">By: {{ $stockTransfer->branchManagerApprovedBy->name ?? 'N/A' }}</p>
                        <p class="text-sm text-ink-muted">
                            {{ $stockTransfer->branch_manager_approved_at?->format('d M Y H:i:s') ?? 'N/A' }}
                        </p>
                    @else
                        <div class="flex items-center gap-2 text-ink-muted">
                            <x-badge variant="gray">Pending</x-badge>
                        </div>
                    @endif
                </div>
            </x-card>

            <x-card title="HQ Approval">
                <div class="space-y-3">
                    @if($stockTransfer->hq_approved_by)
                        <div class="flex items-center gap-2 text-success-text">
                            <x-badge variant="success">Approved</x-badge>
                        </div>
                        <p class="text-sm text-ink-muted">By: {{ $stockTransfer->hqApprovedBy->name ?? 'N/A' }}</p>
                        <p class="text-sm text-ink-muted">
                            {{ $stockTransfer->hq_approved_at?->format('d M Y H:i:s') ?? 'N/A' }}
                        </p>
                    @else
                        <div class="flex items-center gap-2 text-ink-muted">
                            <x-badge variant="gray">Pending</x-badge>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>

        <x-card title="Transfer Items">
            <div class="space-y-6">
                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot:thead>
                            <th class="px-4 py-3 text-left text-xs font-medium text-ink-muted uppercase">Currency</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-ink-muted uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-ink-muted uppercase">Status</th>
                        </x-slot:thead>
                        <x-slot:tbody>
                            @forelse($stockTransfer->items as $item)
                                <tr class="hover:bg-canvas-subtle">
                                    <td class="px-4 py-3 text-sm text-ink">
                                        {{ $item->currency->code ?? 'N/A' }} - {{ $item->currency->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-ink text-right">
                                        {{ number_format((float) $item->value_myr, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <x-badge variant="{{ $item->isFullyReceived() ? 'success' : ((float) ($item->quantity_received ?? '0') > 0 ? 'warning' : 'gray') }}">
                                            {{ $item->isFullyReceived() ? 'Received' : ((float) ($item->quantity_received ?? '0') > 0 ? 'Partially Received' : 'In Transit') }}
                                        </x-badge>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-state message="No items in this transfer." :colspan="3" />
                            @endforelse
                        </x-slot:tbody>
                    </x-table>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="space-y-6">
                <div class="flex flex-wrap items-center gap-3">
                    @if($stockTransfer->canApproveBranchManager())
                        <form action="{{ route('stock-transfers.approve-bm', $stockTransfer->id) }}" method="POST" class="inline">
                            @csrf
                            <x-button type="submit" variant="info">Approve (Branch Manager)</x-button>
                        </form>
                    @endif

                    @if($stockTransfer->canApproveHq())
                        <form action="{{ route('stock-transfers.approve-hq', $stockTransfer->id) }}" method="POST" class="inline">
                            @csrf
                            <x-button type="submit" variant="info">Approve (HQ)</x-button>
                        </form>
                    @endif

                    @if($stockTransfer->canDispatch())
                        <form action="{{ route('stock-transfers.dispatch', $stockTransfer->id) }}" method="POST" class="inline">
                            @csrf
                            <x-button type="submit" variant="primary">Dispatch</x-button>
                        </form>
                    @endif

                    @if($stockTransfer->canReceive())
                        <form action="{{ route('stock-transfers.receive', $stockTransfer->id) }}" method="POST" class="inline">
                            @csrf
                            <x-button type="submit" variant="success">Receive</x-button>
                        </form>
                    @endif

                    @if($stockTransfer->canComplete())
                        <form action="{{ route('stock-transfers.complete', $stockTransfer->id) }}" method="POST" class="inline">
                            @csrf
                            <x-button type="submit" variant="success">Complete</x-button>
                        </form>
                    @endif

                    @if($stockTransfer->canCancel())
                        <div x-data="{ showCancelModal: false }" class="inline">
                            <x-button @click="showCancelModal = true" variant="danger">Cancel</x-button>

                            <div x-show="showCancelModal"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @keydown.escape.window="showCancelModal = false"
                                 @click="showCancelModal = false"
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                                <div class="bg-surface rounded-xl shadow-lg max-w-md w-full mx-4" @click.stop>
                                    <div class="flex items-center justify-between px-5 py-3 border-b border-border">
                                        <h3 class="text-lg font-semibold text-ink">Cancel Stock Transfer</h3>
                                        <button @click="showCancelModal = false" class="text-ink-muted hover:text-ink p-1" aria-label="Close">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="p-6">
                                        <p class="text-sm text-ink-muted">
                                            Are you sure you want to cancel this stock transfer? This action cannot be undone.
                                        </p>
                                    </div>
                                    <div class="flex items-center justify-end gap-3 px-5 py-3 border-t border-border">
                                        <x-button type="button" @click="showCancelModal = false" variant="secondary">Cancel</x-button>
                                        <form action="{{ route('stock-transfers.cancel', $stockTransfer->id) }}" method="POST">
                                            @csrf
                                            <x-button type="submit" variant="danger">Confirm Cancel</x-button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(in_array($stockTransfer->status->value, ['Requested', 'BranchManagerApproved', 'HqApproved', 'InTransit']))
                        <div x-data="{ showRejectModal: false }" class="inline">
                            <x-button @click="showRejectModal = true" variant="danger">Reject</x-button>

                            <div x-show="showRejectModal"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @keydown.escape.window="showRejectModal = false"
                                 @click="showRejectModal = false"
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                                <div class="bg-surface rounded-xl shadow-lg max-w-md w-full mx-4" @click.stop>
                                    <div class="flex items-center justify-between px-5 py-3 border-b border-border">
                                        <h3 class="text-lg font-semibold text-ink">Reject Stock Transfer</h3>
                                        <button @click="showRejectModal = false" class="text-ink-muted hover:text-ink p-1" aria-label="Close">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <form action="{{ route('stock-transfers.reject', $stockTransfer->id) }}" method="POST">
                                        @csrf
                                        <div class="p-6 space-y-4">
                                            <p class="text-sm text-ink-muted">
                                                Are you sure you want to reject this stock transfer? This action cannot be undone.
                                            </p>
                                            <div>
                                                <label for="reject-reason" class="block text-sm font-medium text-ink mb-1">Reason</label>
                                                <textarea id="reject-reason" name="reason" rows="3" required maxlength="500"
                                                    class="w-full rounded-md border-border bg-surface text-ink text-sm focus:border-primary focus:ring-primary"
                                                    placeholder="Why is this transfer being rejected?"></textarea>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-end gap-3 px-5 py-3 border-t border-border">
                                            <x-button type="button" @click="showRejectModal = false" variant="secondary">Cancel</x-button>
                                            <x-button type="submit" variant="danger">Confirm Reject</x-button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
