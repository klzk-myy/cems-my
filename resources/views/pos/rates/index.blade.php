@extends('layouts.base')

@section('title', 'POS Rates')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daily Exchange Rates</h5>
                    <div>
                        <button id="copyYesterdayBtn" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-copy"></i> Copy Yesterday's Rates
                        </button>
                        <button id="saveRatesBtn" class="btn btn-primary btn-sm">
                            <i class="fas fa-save"></i> Save Rates
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="ratesAlert" class="alert" style="display: none;"></div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Currency</th>
                                    <th>Buy Rate</th>
                                    <th>Sell Rate</th>
                                    <th>Mid Rate</th>
                                </tr>
                            </thead>
                            <tbody id="ratesTableBody">
                                <tr>
                                    <td colspan="4" class="text-center">Loading rates...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const currencies = ['USD', 'EUR', 'GBP', 'SGD', 'JPY', 'AUD', 'CAD', 'CHF'];

function loadTodayRates() {
    fetch('/pos/rates/today')
        .then(r => r.json())
        .then(data => renderRatesTable(data));
}

function renderRatesTable(data) {
    const tbody = document.getElementById('ratesTableBody');
    if (!data.rates || Object.keys(data.rates).length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No rates set. Enter rates or copy yesterday.</td></tr>';
        return;
    }
    tbody.innerHTML = currencies.map(c => {
        const rate = data.rates[c] || { buy: '', sell: '', mid: '' };
        return `<tr>
            <td><strong>${c}</strong></td>
            <td><input type="number" step="0.000001" class="form-control rate-input" data-currency="${c}" data-type="buy" value="${rate.buy}"></td>
            <td><input type="number" step="0.000001" class="form-control rate-input" data-currency="${c}" data-type="sell" value="${rate.sell}"></td>
            <td><input type="number" step="0.000001" class="form-control rate-input" data-currency="${c}" data-type="mid" value="${rate.mid}"></td>
        </tr>`;
    }).join('');
}

function saveRates() {
    const rates = {};
    document.querySelectorAll('.rate-input').forEach(input => {
        const currency = input.dataset.currency;
        const type = input.dataset.type;
        if (!rates[currency]) rates[currency] = {};
        rates[currency][type] = parseFloat(input.value) || 0;
    });
    fetch('/pos/rates/set', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ rates })
    }).then(r => r.json()).then(data => {
        showAlert(data.success ? 'success' : 'error', data.message);
        if (data.success) loadTodayRates();
    });
}

function copyYesterdayRates() {
    fetch('/pos/rates/copy-yesterday', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(r => r.json()).then(data => {
        showAlert(data.success ? 'success' : 'error', data.message);
        if (data.success) loadTodayRates();
    });
}

function showAlert(type, message) {
    const alert = document.getElementById('ratesAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';
    setTimeout(() => alert.style.display = 'none', 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    loadTodayRates();
    document.getElementById('saveRatesBtn').addEventListener('click', saveRates);
    document.getElementById('copyYesterdayBtn').addEventListener('click', copyYesterdayRates);
});
</script>
@endpush
@endsection
