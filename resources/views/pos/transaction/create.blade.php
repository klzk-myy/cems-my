@extends('layouts.base')

@section('title', 'New Transaction')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header"><h5 class="mb-0">New Transaction</h5></div>
        <div class="card-body">
            <div id="transactionAlert" class="alert" style="display: none;"></div>
            <form id="transactionForm">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Customer</label>
                        <select class="form-select" name="customer_id" id="customerSelect" required>
                            <option value="">Select Customer</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Counter</label>
                        <select class="form-select" name="till_id" id="tillSelect" required>
                            <option value="">Select Counter</option>
                            @foreach($counters as $counter)
                                <option value="{{ $counter->code }}">{{ $counter->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" id="typeSelect" required>
                            <option value="">Select Type</option>
                            <option value="Buy">Buy (Customer sells MYR)</option>
                            <option value="Sell">Sell (Customer buys MYR)</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency</label>
                        <select class="form-select" name="currency_code" id="currencySelect" required>
                            <option value="">Select Currency</option>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Foreign Amount</label>
                        <input type="number" step="0.01" class="form-control" name="amount_foreign" id="amountForeign" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Purpose</label>
                        <input type="text" class="form-control" name="purpose" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Source of Funds</label>
                        <input type="text" class="form-control" name="source_of_funds" required>
                    </div>
                </div>
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <h6>Quote Preview</h6>
                        <div id="quoteDisplay"><p class="text-muted">Enter details to see quote</p></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" id="calculateQuoteBtn" class="btn btn-outline-primary">Calculate Quote</button>
                    <button type="submit" id="submitBtn" class="btn btn-success" disabled>Create Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentQuote = null;

function calculateQuote() {
    const type = document.getElementById('typeSelect').value;
    const currency = document.getElementById('currencySelect').value;
    const amount = document.getElementById('amountForeign').value;
    const customerId = document.getElementById('customerSelect').value;
    const tillId = document.getElementById('tillSelect').value;

    if (!type || !currency || !amount) {
        document.getElementById('quoteDisplay').innerHTML = '<p class="text-muted">Enter all fields to see quote</p>';
        return;
    }

    fetch('/pos/transactions/quote', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            type, currency_code: currency, amount_foreign: parseFloat(amount),
            customer_id: customerId || null, till_id: tillId || null
        })
    }).then(r => r.json()).then(result => {
        if (result.success) {
            currentQuote = result;
            displayQuote(result.quote, result.validation);
            document.getElementById('submitBtn').disabled = result.validation.errors.length > 0;
        } else {
            showAlert('error', result.error);
        }
    });
}

function displayQuote(quote, validation) {
    let html = `<div class="row">
        <div class="col-md-4"><strong>Rate:</strong> ${quote.rate}</div>
        <div class="col-md-4"><strong>Local Amount:</strong> RM ${parseFloat(quote.amount_local).toFixed(2)}</div>
        <div class="col-md-4"><strong>CDD Level:</strong> ${quote.cdd_level}</div>
    </div>`;
    if (validation.warnings.length) {
        html += '<div class="alert alert-warning mt-2">' + validation.warnings.join('<br>') + '</div>';
    }
    if (validation.errors.length) {
        html += '<div class="alert alert-danger mt-2">' + validation.errors.join('<br>') + '</div>';
    }
    document.getElementById('quoteDisplay').innerHTML = html;
}

function showAlert(type, message) {
    const alert = document.getElementById('transactionAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';
    setTimeout(() => alert.style.display = 'none', 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    ['typeSelect', 'currencySelect', 'amountForeign', 'tillSelect', 'customerSelect'].forEach(id => {
        document.getElementById(id).addEventListener('change', calculateQuote);
    });
    document.getElementById('calculateQuoteBtn').addEventListener('click', calculateQuote);
    document.getElementById('transactionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('/pos/transactions', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        }).then(r => r.json()).then(data => {
            if (data.success) {
                showAlert('success', 'Transaction created');
                setTimeout(() => window.location.href = `/pos/transactions/${data.transaction_id}`, 1500);
            } else {
                showAlert('error', data.message);
            }
        });
    });
});
</script>
@endpush
@endsection
