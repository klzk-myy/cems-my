@extends('layouts.app')

@section('title', 'New Transaction - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Create New Transaction</h2>
    <p class="text-gray-500 text-sm">Record a buy or sell transaction with customer</p>
</div>

@if(session('warning'))
<div class="alert alert-warning" role="alert" aria-live="polite">{{ e(session('warning')) }}</div>
@endif

<form action="/transactions" method="POST" id="transaction-form">
    @csrf

    <!-- Transaction Type Selection -->
    <div class="card">
        <h2>Transaction Type</h2>
        <div class="flex gap-4 mb-6">
            <div class="flex-1">
                <input type="radio" name="type" id="type-buy" value="Buy" {{ old('type') == 'Buy' ? 'checked' : 'checked' }} class="hidden">
                <label for="type-buy" class="block p-4 text-center border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-200 transition-colors {{ old('type') == 'Buy' ? 'border-blue-500 bg-blue-50 text-blue-700' : '' }}">
                    <strong>BUY</strong><br>
                    <small>From Customer</small>
                </label>
            </div>
            <div class="flex-1">
                <input type="radio" name="type" id="type-sell" value="Sell" {{ old('type') == 'Sell' ? 'checked' : '' }} class="hidden">
                <label for="type-sell" class="block p-4 text-center border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-200 transition-colors {{ old('type') == 'Sell' ? 'border-red-500 bg-red-50 text-red-700' : '' }}">
                    <strong>SELL</strong><br>
                    <small>To Customer</small>
                </label>
            </div>
        </div>
    </div>

    <!-- Transaction Details -->
    <div class="card">
        <h2>Transaction Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="mb-5">
                    <label for="customer_id" class="block mb-2 text-sm font-semibold text-gray-800">Customer *</label>
                    <select name="customer_id" id="customer_id" required class="w-full p-3 border-2 border-gray-200 rounded text-sm focus:border-blue-500 focus:outline-none transition-colors">
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->full_name }} ({{ $customer->id_number }})
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="till_id" class="block mb-2 text-sm font-semibold text-gray-800">Till *</label>
                    <select name="till_id" id="till_id" required class="w-full p-3 border-2 border-gray-200 rounded text-sm focus:border-blue-500 focus:outline-none transition-colors">
                        <option value="">Select Till</option>
                        @foreach($tillBalances as $tb)
                            <option value="{{ $tb->till_id }}" data-currency="{{ $tb->currency_code }}" {{ old('till_id') == $tb->till_id ? 'selected' : '' }}>
                                {{ $tb->till_id }} ({{ $tb->currency_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('till_id')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="currency_code" class="block mb-2 text-sm font-semibold text-gray-800">Currency *</label>
                    <select name="currency_code" id="currency_code" required class="w-full p-3 border-2 border-gray-200 rounded text-sm focus:border-blue-500 focus:outline-none transition-colors">
                        <option value="">Select Currency</option>
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->code }}" data-rate-buy="{{ $currency->rate_buy }}" data-rate-sell="{{ $currency->rate_sell }}" {{ old('currency_code') == $currency->code ? 'selected' : '' }}>
                                {{ $currency->code }} - {{ $currency->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('currency_code')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div>
                <div class="mb-5">
                    <label for="amount_foreign" class="block mb-2 text-sm font-semibold text-gray-800">Foreign Amount *</label>
                    <input type="number" step="0.0001" name="amount_foreign" id="amount_foreign" value="{{ old('amount_foreign') }}" required placeholder="0.00" class="w-full p-3 border-2 border-gray-200 rounded text-sm focus:border-blue-500 focus:outline-none transition-colors">
                    @error('amount_foreign')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="rate" class="block mb-2 text-sm font-semibold text-gray-800">Exchange Rate *</label>
                    <input type="number" step="0.000001" name="rate" id="rate" value="{{ old('rate') }}" required placeholder="0.000000" class="w-full p-3 border-2 border-gray-200 rounded text-sm focus:border-blue-500 focus:outline-none transition-colors">
                    <div id="rate-info" class="mt-2 p-3 bg-gray-50 rounded font-mono text-sm text-gray-800">Select currency to see current rates</div>
                    @error('rate')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="amount_local" class="block mb-2 text-sm font-semibold text-gray-800">Local Amount (MYR) *</label>
                    <input type="number" step="0.0001" name="amount_local_display" id="amount_local_display" readonly placeholder="Calculated automatically" class="w-full p-3 border-2 border-gray-200 rounded text-sm bg-gray-50 focus:border-blue-500 focus:outline-none transition-colors">
                    <input type="hidden" name="amount_local" id="amount_local">
                </div>
            </div>
        </div>

        <!-- Calculation Summary -->
        <div class="bg-gray-50 border-2 border-gray-200 rounded-lg p-6 mt-4">
            <div class="flex justify-between py-2 border-b border-gray-200 last:border-b-0 last:font-bold last:text-lg last:text-blue-900 last:pt-4">
                <span>Transaction Type:</span>
                <span id="calc-type">Buy</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span>Foreign Amount:</span>
                <span id="calc-foreign">0.00</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span>Exchange Rate:</span>
                <span id="calc-rate">0.000000</span>
            </div>
            <div class="flex justify-between py-2 font-bold text-lg text-blue-900 pt-4">
                <span>Total (MYR):</span>
                <span id="calc-total">RM 0.00</span>
            </div>
        </div>

        <!-- Compliance Warning -->
        <div id="compliance-warning" class="bg-orange-50 border-2 border-orange-500 rounded-lg p-4 mb-4 hidden">
            <h4 class="text-orange-700 font-semibold mb-1">⚠️ Compliance Alert</h4>
            <p class="text-orange-800 text-sm">This transaction exceeds RM 50,000 and will require manager approval.</p>
        </div>

        <!-- Stock Info for Sell -->
        <div id="stock-info" class="bg-green-50 border-2 border-green-500 rounded-lg p-4 mb-4 hidden">
            <h4 class="text-green-700 font-semibold mb-1">📦 Current Stock</h4>
            <p class="text-green-800 text-sm">Available: <span id="stock-available">-</span></p>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="card">
        <h2>Additional Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="mb-5">
                <label for="purpose" class="block mb-2 text-sm font-semibold text-gray-800">Purpose *</label>
                <select name="purpose" id="purpose" required class="w-full p-3 border-2 border-gray-200 rounded text-sm focus:border-blue-500 focus:outline-none transition-colors">
                    <option value="">Select Purpose</option>
                    <option value="Travel" {{ old('purpose') == 'Travel' ? 'selected' : '' }}>Travel</option>
                    <option value="Business" {{ old('purpose') == 'Business' ? 'selected' : '' }}>Business</option>
                    <option value="Education" {{ old('purpose') == 'Education' ? 'selected' : '' }}>Education</option>
                    <option value="Family Support" {{ old('purpose') == 'Family Support' ? 'selected' : '' }}>Family Support</option>
                    <option value="Investment" {{ old('purpose') == 'Investment' ? 'selected' : '' }}>Investment</option>
                    <option value="Other" {{ old('purpose') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('purpose')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-5">
                <label for="source_of_funds" class="block mb-2 text-sm font-semibold text-gray-800">Source of Funds *</label>
                <select name="source_of_funds" id="source_of_funds" required class="w-full p-3 border-2 border-gray-200 rounded text-sm focus:border-blue-500 focus:outline-none transition-colors">
                    <option value="">Select Source</option>
                    <option value="Salary" {{ old('source_of_funds') == 'Salary' ? 'selected' : '' }}>Salary</option>
                    <option value="Savings" {{ old('source_of_funds') == 'Savings' ? 'selected' : '' }}>Savings</option>
                    <option value="Business Income" {{ old('source_of_funds') == 'Business Income' ? 'selected' : '' }}>Business Income</option>
                    <option value="Loan" {{ old('source_of_funds') == 'Loan' ? 'selected' : '' }}>Loan</option>
                    <option value="Gift" {{ old('source_of_funds') == 'Gift' ? 'selected' : '' }}>Gift</option>
                    <option value="Other" {{ old('source_of_funds') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('source_of_funds')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="flex gap-4 mt-6 pt-6 border-t-2 border-gray-200">
        <a href="/transactions" class="px-6 py-3 bg-gray-200 text-gray-700 no-underline rounded font-semibold hover:bg-gray-300 transition-colors">Cancel</a>
        <button type="submit" class="btn btn-success">Create Transaction</button>
    </div>
</form>
@endsection

@section('scripts')
<script>
    // Transaction calculation
    const typeBuy = document.getElementById('type-buy');
    const typeSell = document.getElementById('type-sell');
    const amountForeign = document.getElementById('amount_foreign');
    const rateInput = document.getElementById('rate');
    const amountLocalDisplay = document.getElementById('amount_local_display');
    const amountLocal = document.getElementById('amount_local');
    const currencySelect = document.getElementById('currency_code');
    const rateInfo = document.getElementById('rate-info');
    const complianceWarning = document.getElementById('compliance-warning');
    const stockInfo = document.getElementById('stock-info');

    // Calculation elements
    const calcType = document.getElementById('calc-type');
    const calcForeign = document.getElementById('calc-foreign');
    const calcRate = document.getElementById('calc-rate');
    const calcTotal = document.getElementById('calc-total');

    function calculate() {
        const foreign = parseFloat(amountForeign.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;
        const local = foreign * rate;

        amountLocalDisplay.value = local.toFixed(4);
        amountLocal.value = local.toFixed(4);

        // Update calculation display
        calcType.textContent = typeSell.checked ? 'Sell' : 'Buy';
        calcForeign.textContent = foreign.toFixed(4) + ' ' + (currencySelect.value || '');
        calcRate.textContent = rate.toFixed(6);
        calcTotal.textContent = 'RM ' + local.toFixed(2);

        // Show compliance warning for large amounts
        if (local >= 50000) {
            complianceWarning.style.display = 'block';
        } else {
            complianceWarning.style.display = 'none';
        }
    }

    // Update rate when currency changes
    currencySelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            const rateBuy = option.getAttribute('data-rate-buy');
            const rateSell = option.getAttribute('data-rate-sell');

            if (typeSell.checked && rateSell) {
                rateInput.value = rateSell;
                rateInfo.textContent = 'Sell Rate: ' + parseFloat(rateSell).toFixed(6);
            } else if (rateBuy) {
                rateInput.value = rateBuy;
                rateInfo.textContent = 'Buy Rate: ' + parseFloat(rateBuy).toFixed(6);
            }

            calculate();
        }
    });

    // Update rate when type changes
    typeBuy.addEventListener('change', function() {
        stockInfo.style.display = 'none';
        const option = currencySelect.options[currencySelect.selectedIndex];
        if (option.value) {
            const rateBuy = option.getAttribute('data-rate-buy');
            if (rateBuy) {
                rateInput.value = rateBuy;
                rateInfo.textContent = 'Buy Rate: ' + parseFloat(rateBuy).toFixed(6);
            }
        }
        calculate();
    });

    typeSell.addEventListener('change', function() {
        stockInfo.style.display = 'block';
        const option = currencySelect.options[currencySelect.selectedIndex];
        if (option.value) {
            const rateSell = option.getAttribute('data-rate-sell');
            if (rateSell) {
                rateInput.value = rateSell;
                rateInfo.textContent = 'Sell Rate: ' + parseFloat(rateSell).toFixed(6);
            }
        }
        calculate();
    });

    // Calculate on input changes
    amountForeign.addEventListener('input', calculate);
    rateInput.addEventListener('input', calculate);

    // Initial calculation
    calculate();
</script>
@endsection
