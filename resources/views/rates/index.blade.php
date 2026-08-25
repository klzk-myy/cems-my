    <div x-data="{ showOverride: false, currency: '' }"
         @override-rate.window="currency = $event.detail.currency; showOverride = true"
         @keydown.escape.window="showOverride = false"
         x-cloak>
        <div x-show="showOverride"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @keydown.escape.window="showOverride = false"
             @click="showOverride = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             role="dialog"
             aria-modal="true"
             aria-labelledby="override-modal-title">
            <div class="bg-surface rounded-xl shadow-lg max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between px-5 py-3 border-b border-border">
                    <h3 id="override-modal-title" class="text-lg font-semibold text-ink">Override Rate</h3>
                    <button @click="showOverride = false" class="text-ink-muted hover:text-ink p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="override-form" method="POST" action="{{ route('rates.override') }}">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-ink-muted mb-1">Currency</label>
                            <x-input type="text" name="currency_code" x-model="currency" readonly />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-muted mb-1">Rate Buy</label>
                            <x-input type="text" name="rate_buy" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-muted mb-1">Rate Sell</label>
                            <x-input type="text" name="rate_sell" required />
                        </div>
                        <x-textarea name="reason" label="Reason" rows="2"></x-textarea>
                    </div>
                    <div class="flex items-center justify-end gap-3 px-5 py-3 border-t border-border">
                        <x-button type="button" @click="showOverride = false" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Save Override</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>