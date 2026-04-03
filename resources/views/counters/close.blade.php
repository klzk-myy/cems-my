@extends('layouts.app')

@section('title', 'Close Counter - CEMS-MY')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Close Counter - {{ $counter->code }}</h1>

    <!-- Counter Info Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Counter Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Code:</strong> {{ $counter->code }}</p>
                    <p><strong>Name:</strong> {{ $counter->name }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Opened By:</strong> {{ $session->openedByUser->name }}</p>
                    <p><strong>Opened At:</strong> {{ $session->opened_at->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Closing Floats Form -->
    <div class="card">
        <div class="card-body">
            <form action="{{ route('counters.close', $counter) }}" method="POST">
                @csrf

                <h5 class="mb-3">Closing Floats</h5>
                <div id="floats-container">
                    @foreach($currencies as $index => $currency)
                    <div class="float-row mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">{{ $currency->code }} - {{ $currency->name }}</label>
                                <input type="hidden" name="closing_floats[{{ $index }}][currency_id]" value="{{ $currency->id }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Opening Balance</label>
                                <input type="text" class="form-control" value="{{ number_format(10000.00, 2) }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Closing Amount</label>
                                <input type="number" step="0.01" class="form-control closing-amount" name="closing_floats[{{ $index }}][amount]" required min="0" data-opening="10000.00">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Variance</label>
                                <input type="text" class="form-control variance-display" readonly>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Total Variance:</strong> <span id="total-variance">RM 0.00</span>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Required if variance > RM 100"></textarea>
                </div>

                <button type="submit" class="btn btn-warning">Close Counter</button>
                <a href="{{ route('counters.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.closing-amount').forEach(input => {
        input.addEventListener('input', function() {
            const opening = parseFloat(this.dataset.opening);
            const closing = parseFloat(this.value) || 0;
            const variance = closing - opening;

            const varianceDisplay = this.closest('.row').querySelector('.variance-display');
            varianceDisplay.value = 'RM ' + variance.toFixed(2);

            if (variance < 0) {
                varianceDisplay.classList.add('text-danger');
            } else if (variance > 0) {
                varianceDisplay.classList.add('text-success');
            } else {
                varianceDisplay.classList.remove('text-danger', 'text-success');
            }

            updateTotalVariance();
        });
    });

    function updateTotalVariance() {
        let total = 0;
        document.querySelectorAll('.closing-amount').forEach(input => {
            const opening = parseFloat(input.dataset.opening);
            const closing = parseFloat(input.value) || 0;
            total += (closing - opening);
        });

        const totalDisplay = document.getElementById('total-variance');
        totalDisplay.textContent = 'RM ' + total.toFixed(2);

        if (Math.abs(total) > 500) {
            totalDisplay.parentElement.classList.remove('alert-info', 'alert-warning');
            totalDisplay.parentElement.classList.add('alert-danger');
        } else if (Math.abs(total) > 100) {
            totalDisplay.parentElement.classList.remove('alert-info', 'alert-danger');
            totalDisplay.parentElement.classList.add('alert-warning');
        } else {
            totalDisplay.parentElement.classList.remove('alert-warning', 'alert-danger');
            totalDisplay.parentElement.classList.add('alert-info');
        }
    }
</script>
@endsection
