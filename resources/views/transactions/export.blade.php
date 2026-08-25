<x-app-layout title="Export Transactions">
    <div class="space-y-6">
        <x-page-header title="Export Transactions" description="Export transaction data to CSV" />

        <x-card>
            <form action="{{ route('transactions.export.export') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-ink mb-1">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-ink mb-1">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-ink mb-1">Branch</label>
                        <select name="branch_id" id="branch_id" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-medium text-ink mb-1">Transaction Type</label>
                        <select name="type" id="type" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                            <option value="">All Types</option>
                            @foreach($types as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="status" class="block text-sm font-medium text-ink mb-1">Status</label>
                        <select name="status" id="status" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <x-button type="submit" variant="primary">Export CSV</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
