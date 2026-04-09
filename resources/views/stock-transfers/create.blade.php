@extends('layouts.app')

@section('title', 'New Stock Transfer - CEMS-MY')

@section('content')
<h1 class="text-2xl font-bold mb-6">New Stock Transfer</h1>

<form action="{{ route('stock-transfers.store') }}" method="POST">
    @csrf
    <div class="card mb-6">
        <div class="card-header"><h3>Transfer Details</h3></div>
        <div class="card-body">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Source Branch</label>
                    <input type="text" name="source_branch_name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Destination Branch</label>
                    <input type="text" name="destination_branch_name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="Standard">Standard</option>
                        <option value="Emergency">Emergency</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Return">Return</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3>Items</h3></div>
        <div class="card-body">
            <div id="items-container">
                <div class="grid grid-cols-5 gap-2 mb-2 item-row">
                    <input type="text" name="items[0][currency_code]" placeholder="Currency (e.g. USD)" class="form-input" required>
                    <input type="number" name="items[0][quantity]" placeholder="Qty" step="0.0001" class="form-input" required>
                    <input type="number" name="items[0][rate]" placeholder="Rate" step="0.000001" class="form-input" required>
                    <input type="number" name="items[0][value_myr]" placeholder="Value MYR" step="0.01" class="form-input" required>
                    <button type="button" class="btn btn-secondary" onclick="this.parentElement.remove()">Remove</button>
                </div>
            </div>
            <button type="button" class="btn btn-secondary mt-2" onclick="addItemRow()">Add Item</button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Create Transfer</button>
</form>

<script>
let itemIndex = 1;
function addItemRow() {
    const container = document.getElementById('items-container');
    const row = document.createElement('div');
    row.className = 'grid grid-cols-5 gap-2 mb-2 item-row';
    row.innerHTML = `
        <input type="text" name="items[${itemIndex}][currency_code]" placeholder="Currency" class="form-input" required>
        <input type="number" name="items[${itemIndex}][quantity]" placeholder="Qty" step="0.0001" class="form-input" required>
        <input type="number" name="items[${itemIndex}][rate]" placeholder="Rate" step="0.000001" class="form-input" required>
        <input type="number" name="items[${itemIndex}][value_myr]" placeholder="Value MYR" step="0.01" class="form-input" required>
        <button type="button" class="btn btn-secondary" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(row);
    itemIndex++;
}
</script>
@endsection
