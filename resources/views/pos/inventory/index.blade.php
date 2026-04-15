@extends('layouts.base')

@section('title', 'POS Inventory')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Inventory Dashboard</h5>
            <button id="refreshBtn" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            <div id="inventoryAlert" class="alert" style="display: none;"></div>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>Total Currencies</h6>
                            <h3 id="totalCurrencies">-</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning bg-opacity-10">
                        <div class="card-body">
                            <h6>Low Stock</h6>
                            <h3 id="lowStockCount">-</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success bg-opacity-10">
                        <div class="card-body">
                            <h6>Normal Stock</h6>
                            <h3 id="normalStockCount">-</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Currency</th>
                            <th>Total Balance</th>
                            <th>Total Value (MYR)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <tr><td colspan="4" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
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
    });
}

function renderTable(inventory) {
    const tbody = document.getElementById('inventoryTableBody');
    if (!inventory || !inventory.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No inventory data</td></tr>';
        return;
    }
    tbody.innerHTML = inventory.map(item => {
        const cls = item.status === 'low' ? 'danger' : (item.status === 'medium' ? 'warning' : 'success');
        return `<tr>
            <td><strong>${item.currency_code}</strong> - ${item.currency_name}</td>
            <td>${parseFloat(item.total_balance).toLocaleString()} ${item.currency_code}</td>
            <td>RM ${parseFloat(item.total_value_myr || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td><span class="badge bg-${cls}">${item.status}</span></td>
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
