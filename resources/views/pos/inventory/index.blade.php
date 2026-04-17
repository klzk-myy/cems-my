@extends('layouts.base')

@section('title', 'Inventory')

@section('header-title')
<div>
    <h1 class="text-2xl font-semibold text-[--color-ink]">Inventory Dashboard</h1>
    <p class="text-sm text-[--color-ink-muted]">Stock levels across all counters</p>
</div>
@endsection

@section('header-actions')
<button id="refreshBtn" class="btn btn-ghost">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
    Refresh
</button>
@endsection

@section('content')
<div id="inventoryAlert" class="alert mb-6" style="display: none;"></div>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <p class="text-sm text-[--color-ink-muted]">Total Currencies</p>
            <p class="text-2xl font-bold" id="totalCurrencies">-</p>
        </div>
    </div>
    <div class="card border-l-4 border-yellow-500">
        <div class="card-body">
            <p class="text-sm text-[--color-ink-muted]">Low Stock</p>
            <p class="text-2xl font-bold text-yellow-600" id="lowStockCount">-</p>
        </div>
    </div>
    <div class="card border-l-4 border-green-500">
        <div class="card-body">
            <p class="text-sm text-[--color-ink-muted]">Normal Stock</p>
            <p class="text-2xl font-bold text-green-600" id="normalStockCount">-</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Currency</th>
                    <th>Total Balance</th>
                    <th>Total Value (MYR)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                <tr>
                    <td colspan="4" class="text-center py-8 text-[--color-ink-muted]">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
function loadInventory() {
    Promise.all([
        fetch('/pos/inventory/aggregate').then(r => r.json()),
        fetch('/pos/inventory/low-stock').then(r => r.json()),
    ]).then(([inv, low]) => {
        if (inv.success) renderTable(inv.inventory);
        if (inv.success && low.success) {
            document.getElementById('totalCurrencies').textContent = inv.inventory.length;
            document.getElementById('lowStockCount').textContent = Object.keys(low.low_stock).length;
            document.getElementById('normalStockCount').textContent = inv.inventory.length - Object.keys(low.low_stock).length;
        }
    }).catch(err => {
        console.error('Failed to load inventory:', err);
    });
}

function renderTable(inventory) {
    const tbody = document.getElementById('inventoryTableBody');
    if (!inventory || !inventory.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-[--color-ink-muted]">No inventory data</td></tr>';
        return;
    }
    tbody.innerHTML = inventory.map(item => {
        const statusClass = item.status === 'low' ? 'badge-danger' : (item.status === 'medium' ? 'badge-warning' : 'badge-success');
        const statusLabel = item.status.charAt(0).toUpperCase() + item.status.slice(1);
        return `<tr>
            <td class="font-medium">${item.currency_code} - ${item.currency_name}</td>
            <td class="font-mono">${parseFloat(item.total_balance).toLocaleString()} ${item.currency_code}</td>
            <td class="font-mono">RM ${parseFloat(item.total_value_myr || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td><span class="badge ${statusClass}">${statusLabel}</span></td>
        </tr>`;
    }).join('');
}

document.addEventListener('DOMContentLoaded', function() {
    loadInventory();
    document.getElementById('refreshBtn').addEventListener('click', loadInventory);
    setInterval(loadInventory, 60000);
});
</script>
@endpush
@endsection
