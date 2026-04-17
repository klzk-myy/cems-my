@extends('layouts.base')

@section('title', 'Daily Rates')

@section('header-title')
<div>
    <h1 class="text-2xl font-semibold text-[--color-ink]">Daily Exchange Rates</h1>
    <p class="text-sm text-[--color-ink-muted]">{{ now()->format('d M Y') }}</p>
</div>
@endsection

@section('header-actions')
<div class="flex gap-2">
    <button id="copyYesterdayBtn" class="btn btn-ghost">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
        </svg>
        Copy Yesterday
    </button>
    <button id="saveRatesBtn" class="btn btn-primary">Save Rates</button>
</div>
@endsection

@section('content')
<div id="ratesAlert" class="alert mb-6" style="display: none;"></div>

<div class="card">
    <div class="table-container">
        <table class="table">
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
                    <td colspan="4" class="text-center py-8 text-[--color-ink-muted]">Loading rates...</td>
                </tr>
            </tbody>
        </table>
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
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-[--color-ink-muted]">No rates set. Click "Copy Yesterday" or enter rates manually.</td></tr>';
        return;
    }
    tbody.innerHTML = currencies.map(c => {
        const rate = data.rates[c] || { buy: '', sell: '', mid: '' };
        return `<tr>
            <td class="font-medium">${c}</td>
            <td><input type="number" step="0.000001" class="form-input rate-input font-mono" data-currency="${c}" data-type="buy" value="${rate.buy}" placeholder="0.000000"></td>
            <td><input type="number" step="0.000001" class="form-input rate-input font-mono" data-currency="${c}" data-type="sell" value="${rate.sell}" placeholder="0.000000"></td>
            <td><input type="number" step="0.000001" class="form-input rate-input font-mono" data-currency="${c}" data-type="mid" value="${rate.mid}" placeholder="0.000000"></td>
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
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
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
    alert.className = `alert alert-${type} mb-6`;
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
