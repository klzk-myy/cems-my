@extends('layouts.app')

@section('title', 'Open Counter - CEMS-MY')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Open Counter - {{ $counter->code }}</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('counters.open', $counter) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Counter</label>
                    <input type="text" class="form-control" value="{{ $counter->code }} - {{ $counter->name }}" readonly>
                </div>

                <h5 class="mt-4 mb-3">Opening Floats</h5>
                <div id="floats-container">
                    <div class="float-row mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Currency</label>
                                <select class="form-select" name="opening_floats[0][currency_id]" required>
                                    <option value="">Select currency</option>
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" class="form-control" name="opening_floats[0][amount]" required min="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-block" onclick="removeFloatRow(this)">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" id="add-float" class="btn btn-secondary mb-3">+ Add Currency</button>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes (Optional)</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Open Counter</button>
                <a href="{{ route('counters.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('add-float').addEventListener('click', function() {
        const container = document.getElementById('floats-container');
        const rowCount = container.querySelectorAll('.float-row').length;

        const newRow = document.createElement('div');
        newRow.className = 'float-row mb-3';
        newRow.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Currency</label>
                    <select class="form-select" name="opening_floats[${rowCount}][currency_id]" required>
                        <option value="">Select currency</option>
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" class="form-control" name="opening_floats[${rowCount}][amount]" required min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-block" onclick="removeFloatRow(this)">Remove</button>
                </div>
            </div>
        `;

        container.appendChild(newRow);
    });

    function removeFloatRow(button) {
        const row = button.closest('.float-row');
        if (document.querySelectorAll('.float-row').length > 1) {
            row.remove();
        }
    }
</script>
@endsection
