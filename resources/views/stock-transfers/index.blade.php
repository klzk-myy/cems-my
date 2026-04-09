<x-layouts.app title="Stock Transfers">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Stock Transfers</h1>
        @can('create', App\Models\StockTransfer::class)
        <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">New Transfer</a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="flex gap-4 mb-6">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Requested">Requested</option>
                    <option value="BranchManagerApproved">BM Approved</option>
                    <option value="HQApproved">HQ Approved</option>
                    <option value="InTransit">In Transit</option>
                    <option value="PartiallyReceived">Partially Received</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <input type="text" name="source_branch" placeholder="Source Branch" class="form-input">
                <input type="text" name="destination_branch" placeholder="Destination Branch" class="form-input">
                <button type="submit" class="btn btn-secondary">Filter</button>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Transfer #</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Destination</th>
                        <th>Total (MYR)</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                    <tr>
                        <td><a href="{{ route('stock-transfers.show', $transfer) }}">{{ $transfer->transfer_number }}</a></td>
                        <td>{{ $transfer->type }}</td>
                        <td><span class="badge badge-{{ strtolower($transfer->status) }}">{{ $transfer->status }}</span></td>
                        <td>{{ $transfer->source_branch_name }}</td>
                        <td>{{ $transfer->destination_branch_name }}</td>
                        <td>{{ number_format($transfer->total_value_myr, 2) }}</td>
                        <td>{{ $transfer->requested_at?->format('Y-m-d') }}</td>
                        <td><a href="{{ route('stock-transfers.show', $transfer) }}">View</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No transfers found</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $transfers->links() }}
        </div>
    </div>
</x-layouts.app>