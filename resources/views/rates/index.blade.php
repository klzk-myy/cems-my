<x-app-layout title="Exchange Rates">
    <div class="space-y-6">
        <x-page-header title="Exchange Rates" :actions="true">
            <x-slot:actions>
                @if($currentBranch)
                    <span class="text-sm text-ink-muted">{{ $currentBranch->name }}</span>
                @endif
            </x-slot:actions>
        </x-page-header>

        <x-filter-bar>
            @if($canSelectBranch)
                <x-select name="branch" placeholder="All Branches" inline />
            @endif
            <x-select
                name="date"
                :options="array_combine($availableDates, $availableDates)"
                :selected="$availableDates[0] ?? null"
                placeholder=""
                inline
            />
            <x-button
                x-data="{ loading: false }"
                @click="loading = true; fetch('{{ route('api.v1.rates.fetch') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(r => r.json()).then(d => { if (d.success) { window.location.reload(); } else { window.dispatchEvent(new CustomEvent('rate-error', { detail: { message: d.message || 'Failed to fetch rates' } })); } }).catch(e => { window.dispatchEvent(new CustomEvent('rate-error', { detail: { message: e.message || 'Network error' } })); }).finally(() => loading = false)"
                x-bind:disabled="loading"
                variant="primary"
                type="button">
                <span x-show="!loading">Fetch from API</span>
                <span x-show="loading">Fetching…</span>
            </x-button>
            <x-button
                x-data="{ loading: false }"
                @click="loading = true; fetch('{{ route('api.v1.rates.copy-previous') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(r => r.json()).then(d => { if (d.success) { window.location.reload(); } else { window.dispatchEvent(new CustomEvent('rate-error', { detail: { message: d.message || 'Failed to copy previous rates' } })); } }).catch(e => { window.dispatchEvent(new CustomEvent('rate-error', { detail: { message: e.message || 'Network error' } })); }).finally(() => loading = false)"
                x-bind:disabled="loading"
                variant="secondary"
                type="button">
                <span x-show="!loading">Copy Previous</span>
                <span x-show="loading">Copying…</span>
            </x-button>
        </x-filter-bar>

        <div x-data="{ error: '' }"
             @rate-error.window="error = $event.detail.message"
             x-show="error"
             x-transition
             x-cloak
             class="p-4 bg-danger-subtle border border-danger-border rounded-lg text-sm text-danger-text mb-4"
             role="alert">
            <span x-text="error"></span>
        </div>

        <x-card>
            <x-table>
                <x-slot:thead>
                    <th class="px-4 py-3 text-left text-xs font-medium text-ink-muted uppercase">Currency</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-ink-muted uppercase">Rate Buy</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-ink-muted uppercase">Rate Sell</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-ink-muted uppercase">Spread</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-ink-muted uppercase">Source</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-ink-muted uppercase">Updated</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-ink-muted uppercase">Actions</th>
                </x-slot:thead>
                <x-slot:tbody>
                    @forelse($rates as $rate)
                        <tr class="hover:bg-canvas-subtle">
                            <td class="px-4 py-3 text-sm font-medium text-ink">{{ $rate['currency_code'] }}</td>
                            <td class="px-4 py-3 text-sm text-right text-ink">{{ number_format((float)$rate['rate_buy'], 4) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-ink">{{ number_format((float)$rate['rate_sell'], 4) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-ink-muted">{{ number_format((float)($rate['spread'] ?? 0), 2) }}%</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <x-badge variant="{{ $rate['source'] === 'manual' ? 'warning' : 'success' }}">
                                    {{ ucfirst($rate['source'] ?? 'api') }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-ink-muted">
                                {{ $rate['fetched_at'] ? \Carbon\Carbon::parse($rate['fetched_at'])->format('H:i:s') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <x-button
                                    variant="ghost"
                                    size="sm"
                                    @click="$dispatch('override-rate', { currency: '{{ $rate['currency_code'] }}' })">
                                    Override
                                </x-button>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state message='No rates available. Click "Fetch from API" to get current rates.' :colspan="7" />
                    @endforelse
                </x-slot:tbody>
            </x-table>
        </x-card>
    </div>

    <!-- Override Modal -->
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
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-surface rounded-xl shadow-lg max-w-md w-full mx-4" @click.stop>
                <div class="flex items-center justify-between px-5 py-3 border-b border-border">
                    <h3 class="text-lg font-semibold text-ink">Override Rate</h3>
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
</x-app-layout>
