<x-app-layout title="EOD Reconciliation">
    <div class="space-y-6">
        <x-page-header title="EOD Reconciliation" description="End-of-Day reconciliation dashboard for managers">
            <x-slot:actions>
                <form method="GET" action="{{ route('eod.dashboard') }}" class="flex gap-2 items-center">
                    <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="rounded-md border-border bg-surface text-ink text-sm">
                    <x-button type="submit" variant="primary">View</x-button>
                </form>
            </x-slot:actions>
        </x-page-header>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <x-card>
                <div class="text-sm text-ink-muted">Total Counters</div>
                <div class="text-2xl font-bold mt-1">{{ $report['summary']['total_counters'] ?? 0 }}</div>
            </x-card>
            <x-card>
                <div class="text-sm text-ink-muted">Active</div>
                <div class="text-2xl font-bold mt-1 text-green-600">{{ $report['summary']['active_counters'] ?? 0 }}</div>
            </x-card>
            <x-card>
                <div class="text-sm text-ink-muted">Closed</div>
                <div class="text-2xl font-bold mt-1 text-blue-600">{{ $report['summary']['closed_counters'] ?? 0 }}</div>
            </x-card>
            <x-card>
                <div class="text-sm text-ink-muted">Handed Over</div>
                <div class="text-2xl font-bold mt-1 text-yellow-600">{{ $report['summary']['handed_over_counters'] ?? 0 }}</div>
            </x-card>
        </div>

        <x-card title="Cash Flow Totals">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="text-left py-2 px-4">Description</th>
                            <th class="text-right py-2 px-4">Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-border">
                            <td class="py-2 px-4">Opening Float</td>
                            <td class="py-2 px-4 text-right">{{ number_format((float) ($report['totals']['opening_float'] ?? 0), 2) }}</td>
                        </tr>
                        <tr class="border-b border-border">
                            <td class="py-2 px-4">Cash Received</td>
                            <td class="py-2 px-4 text-right">{{ number_format((float) ($report['totals']['cash_received'] ?? 0), 2) }}</td>
                        </tr>
                        <tr class="border-b border-border">
                            <td class="py-2 px-4">Cash Paid</td>
                            <td class="py-2 px-4 text-right">{{ number_format((float) ($report['totals']['cash_paid'] ?? 0), 2) }}</td>
                        </tr>
                        <tr class="border-b border-border font-bold">
                            <td class="py-2 px-4">Closing Float</td>
                            <td class="py-2 px-4 text-right">{{ number_format((float) ($report['totals']['closing_float'] ?? 0), 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-card>

        @if(!empty($report['variance_status']))
            @php
                $vs = $report['variance_status'];
                $alertClass = match($vs['status'] ?? 'ok') {
                    'critical' => 'bg-red-50 border-red-200 text-red-800',
                    'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                    'minor' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                    default => 'bg-green-50 border-green-200 text-green-800'
                };
                $varianceLabel = match($vs['status'] ?? 'ok') {
                    'critical' => 'CRITICAL VARIANCE',
                    'warning' => 'Variance Warning',
                    'minor' => 'Minor Variance',
                    default => 'No Variance'
                };
            @endphp
            <x-card>
                <div class="p-4 rounded-lg border {{ $alertClass }}">
                    <strong>{{ $varianceLabel }}</strong>
                    @if(isset($vs['variance_amount'])) — RM {{ number_format((float) $vs['variance_amount'], 2) }}{{ endif }}
                </div>
            </x-card>
        @endif

        @if(!empty($report['counter_details']))
            <x-card title="Counter Details">
                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot:thead>
                            <th>Counter</th>
                            <th>Status</th>
                            <th>Teller</th>
                            <th>Opening Float</th>
                            <th>Closing Float</th>
                            <th>Variance</th>
                        </x-slot:thead>
                        <x-slot:tbody>
                            @foreach($report['counter_details'] as $counter)
                                <tr>
                                    <td>{{ $counter['counter_name'] ?? 'N/A' }}</td>
                                    <td>
                                        <x-badge variant="{{ ($counter['status'] ?? '') === 'Closed' ? 'success' : 'warning' }}">
                                            {{ $counter['status'] ?? 'Unknown' }}
                                        </x-badge>
                                    </td>
                                    <td>{{ $counter['teller_name'] ?? 'N/A' }}</td>
                                    <td>{{ number_format((float) ($counter['opening_float'] ?? 0), 2) }}</td>
                                    <td>{{ number_format((float) ($counter['closing_float'] ?? 0), 2) }}</td>
                                    <td class="{{ (float) ($counter['variance'] ?? 0) !== 0.0 ? 'text-red-600 font-bold' : '' }}">
                                        {{ number_format((float) ($counter['variance'] ?? 0), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot:tbody>
                    </x-table>
                </div>
            </x-card>
        @endif
    </div>
</x-app-layout>
