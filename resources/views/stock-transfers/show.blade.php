<x-layouts.app title="Stock Transfer {{ $stockTransfer->transfer_number }}">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">{{ $stockTransfer->transfer_number }}</h1>
            <p class="text-sm text-gray-500">
                {{ $stockTransfer->source_branch_name }} → {{ $stockTransfer->destination_branch_name }}
            </p>
        </div>
        <div>
            <span class="badge badge-{{ strtolower($stockTransfer->status) }}">{{ $stockTransfer->status }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header"><h3>Transfer Details</h3></div>
            <div class="card-body">
                <dl class="grid grid-cols-2 gap-4">
                    <div><dt class="text-sm text-gray-500">Type</dt><dd>{{ $stockTransfer->type }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Status</dt><dd>{{ $stockTransfer->status }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Requested By</dt><dd>{{ $stockTransfer->requestedBy?->name }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Requested At</dt><dd>{{ $stockTransfer->requested_at?->format('Y-m-d H:i') }}</dd></div>
                    @if($stockTransfer->branchManagerApprovedBy)
                    <div><dt class="text-sm text-gray-500">BM Approved By</dt><dd>{{ $stockTransfer->branchManagerApprovedBy?->name }}</dd></div>
                    @endif
                    @if($stockTransfer->hqApprovedBy)
                    <div><dt class="text-sm text-gray-500">HQ Approved By</dt><dd>{{ $stockTransfer->hqApprovedBy?->name }}</dd></div>
                    @endif
                    <div><dt class="text-sm text-gray-500">Total Value</dt><dd>MYR {{ number_format($stockTransfer->total_value_myr, 2) }}</dd></div>
                    @if($stockTransfer->cancellation_reason)
                    <div class="col-span-2"><dt class="text-sm text-gray-500">Cancellation Reason</dt><dd class="text-red-600">{{ $stockTransfer->cancellation_reason }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Items</h3></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Currency</th><th>Qty</th><th>Rate</th><th>Value (MYR)</th><th>Received</th></tr></thead>
                    <tbody>
                        @foreach($stockTransfer->items as $item)
                        <tr>
                            <td>{{ $item->currency_code }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->rate }}</td>
                            <td>{{ number_format($item->value_myr, 2) }}</td>
                            <td>{{ $item->quantity_received ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($stockTransfer->status === 'Requested' && auth()->user()->role->value === 'manager')
    <form action="{{ route('stock-transfers.approve-bm', $stockTransfer) }}" method="POST" class="mt-4">
        @csrf
        <button type="submit" class="btn btn-primary">Approve as Branch Manager</button>
    </form>
    @endif
</x-layouts.app>