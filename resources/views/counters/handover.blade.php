@extends('layouts.app')

@section('title', 'Counter Handover - CEMS-MY')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Handover Counter - {{ $counter->code }}</h1>

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
                    <p><strong>Current User:</strong> {{ $session->user->name }}</p>
                    <p><strong>Session Started:</strong> {{ $session->opened_at->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Handover Form -->
    <div class="card">
        <div class="card-body">
            <form action="{{ route('counters.handover', $counter) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="to_user_id" class="form-label">Handover To</label>
                    <select class="form-select" id="to_user_id" name="to_user_id" required>
                        <option value="">Select user</option>
                        @foreach($availableUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="supervisor_id" class="form-label">Supervisor</label>
                    <select class="form-select" id="supervisor_id" name="supervisor_id" required>
                        <option value="">Select supervisor</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <h5 class="mt-4 mb-3">Physical Count</h5>
                <div id="counts-container">
                    @foreach($currencies as $index => $currency)
                    <div class="count-row mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">{{ $currency->code }} - {{ $currency->name }}</label>
                                <input type="hidden" name="physical_counts[{{ $index }}][currency_id]" value="{{ $currency->id }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Expected Amount</label>
                                <input type="text" class="form-control" value="{{ number_format(10000.00, 2) }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Physical Count</label>
                                <input type="number" step="0.01" class="form-control physical-count" name="physical_counts[{{ $index }}][amount]" required min="0" data-expected="10000.00">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Total Variance:</strong> <span id="total-variance">RM 0.00</span>
                </div>

                <div class="mb-3">
                    <label for="variance_notes" class="form-label">Variance Notes</label>
                    <textarea class="form-control" id="variance_notes" name="variance_notes" rows="3" placeholder="Required if variance > 0"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Complete Handover</button>
                <a href="{{ route('counters.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.physical-count').forEach(input => {
        input.addEventListener('input', function() {
            updateTotalVariance();
        });
    });

    function updateTotalVariance() {
        let total = 0;
        document.querySelectorAll('.physical-count').forEach(input => {
            const expected = parseFloat(input.dataset.expected);
            const actual = parseFloat(input.value) || 0;
            total += (actual - expected);
        });

        const totalDisplay = document.getElementById('total-variance');
        totalDisplay.textContent = 'RM ' + total.toFixed(2);

        if (Math.abs(total) > 500) {
            totalDisplay.parentElement.classList.remove('alert-info', 'alert-warning');
            totalDisplay.parentElement.classList.add('alert-danger');
        } else if (Math.abs(total) > 0) {
            totalDisplay.parentElement.classList.remove('alert-info', 'alert-danger');
            totalDisplay.parentElement.classList.add('alert-warning');
        } else {
            totalDisplay.parentElement.classList.remove('alert-warning', 'alert-danger');
            totalDisplay.parentElement.classList.add('alert-info');
        }
    }
</script>
@endsection
