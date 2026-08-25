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
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                                 role="dialog"
                                 aria-modal="true"
                                 aria-labelledby="cancel-modal-title">
                                <div class="bg-surface rounded-xl shadow-lg max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
                                    <div class="flex items-center justify-between px-5 py-3 border-b border-border">
                                        <h3 id="cancel-modal-title" class="text-lg font-semibold text-ink">Cancel Stock Transfer</h3>
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
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                                 role="dialog"
                                 aria-modal="true"
                                 aria-labelledby="reject-modal-title">
                                <div class="bg-surface rounded-xl shadow-lg max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
                                    <div class="flex items-center justify-between px-5 py-3 border-b border-border">
                                        <h3 id="reject-modal-title" class="text-lg font-semibold text-ink">Reject Stock Transfer</h3>
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