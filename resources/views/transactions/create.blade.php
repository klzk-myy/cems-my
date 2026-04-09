@extends('layouts.app')

@section('title', 'New Transaction - CEMS-MY')

@section('styles')
<style>
    .transaction-header {
        margin-bottom: 1.5rem;
    }
    .transaction-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .transaction-header p {
        color: #718096;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    .form-group {
        margin-bottom: 1.25rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #2d3748;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.2s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3182ce;
    }
    .form-group .error {
        color: #e53e3e;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .type-selector {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .type-option {
        flex: 1;
    }
    .type-option input {
        display: none;
    }
    .type-option label {
        display: block;
        padding: 1rem;
        text-align: center;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .type-option input:checked + label {
        border-color: #3182ce;
        background: #ebf8ff;
        color: #2b6cb0;
    }
    .type-option.buy label {
        border-left: 4px solid #38a169;
    }
    .type-option.sell label {
        border-left: 4px solid #e53e3e;
    }

    .calculation-box {
        background: #f7fafc;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    .calculation-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .calculation-row:last-child {
        border-bottom: none;
        font-weight: 600;
        font-size: 1.25rem;
        color: #1a365d;
    }

    .rate-display {
        background: #f7fafc;
        padding: 0.75rem;
        border-radius: 4px;
        font-family: monospace;
        font-size: 1.1rem;
        color: #2d3748;
    }

    .compliance-warning {
        background: #fffaf0;
        border: 2px solid #dd6b20;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .compliance-warning h4 {
        color: #c05621;
        margin-bottom: 0.5rem;
    }

    .stock-info {
        background: #f0fff4;
        border: 2px solid #38a169;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .stock-info h4 {
        color: #276749;
        margin-bottom: 0.5rem;
    }

    .actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 2px solid #e2e8f0;
    }
</style>
@endsection

@section('content')
<div class="transaction-header">
    <h2>Create New Transaction</h2>
    <p>Record a buy or sell transaction with customer</p>
</div>

@if(session('warning'))
<div class="alert alert-warning" role="alert" aria-live="polite">{{ e(session('warning')) }}</div>
@endif

<form action="/transactions" method="POST" id="transaction-form">
    @csrf

    <!-- Transaction Type Selection -->
    <div class="card">
        <h2>Transaction Type</h2>
        <div class="type-selector">
            <div class="type-option buy">
                <input type="radio" name="type" id="type-buy" value="Buy" {{ old('type') == 'Buy' ? 'checked' : 'checked' }}>
                <label for="type-buy">
                    <strong>BUY</strong><br>
                    <small>From Customer</small>
                </label>
            </div>
            <div class="type-option sell">
                <input type="radio" name="type" id="type-sell" value="Sell" {{ old('type') == 'Sell' ? 'checked' : '' }}>
                <label for="type-sell">
                    <strong>SELL</strong><br>
                    <small>To Customer</small>
                </label>
            </div>
        </div>
    </div>

    <!-- Transaction Details -->
    <div class="card">
        <h2>Transaction Details</h2>
        <div class="form-grid">
            <div>
                <div class="form-group">
                    <label for="customer_id">Customer *</label>
                    <select name="customer_id" id="customer_id" required>
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->full_name }} ({{ $customer->id_number }})
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="till_id">Till *</label>
                    <select name="till_id" id="till_id" required>
                        <option value="">Select Till</option>
                        @foreach($tillBalances as $tb)
                            <option value="{{ $tb->till_id }}" data-currency="{{ $tb->currency_code }}" {{ old('till_id') == $tb->till_id ? 'selected' : '' }}>
                                {{ $tb->till_id }} ({{ $tb->currency_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('till_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="currency_code">Currency *</label>
                    <select name="currency_code" id="currency_code" required>
                        <option value="">Select Currency</option>
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->code }}" data-rate-buy="{{ $currency->rate_buy }}" data-rate-sell="{{ $currency->rate_sell }}" {{ old('currency_code') == $currency->code ? 'selected' : '' }}>
                                {{ $currency->code }} - {{ $currency->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('currency_code')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label for="amount_foreign">Foreign Amount *</label>
                    <input type="number" step="0.0001" name="amount_foreign" id="amount_foreign" value="{{ old('amount_foreign') }}" required placeholder="0.00">
                    @error('amount_foreign')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="rate">Exchange Rate *</label>
                    <input type="number" step="0.000001" name="rate" id="rate" value="{{ old('rate') }}" required placeholder="0.000000">
                    <div id="rate-info" class="rate-display" style="margin-top: 0.5rem;">Select currency to see current rates</div>
                    @error('rate')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="amount_local">Local Amount (MYR) *</label>
                    <input type="number" step="0.0001" name="amount_local_display" id="amount_local_display" readonly placeholder="Calculated automatically" style="background: #f7fafc;">
                    <input type="hidden" name="amount_local" id="amount_local">
                </div>
            </div>
        </div>

        <!-- Calculation Summary -->
        <div class="calculation-box">
            <div class="calculation-row">
                <span>Transaction Type:</span>
                <span id="calc-type">Buy</span>
            </div>
            <div class="calculation-row">
                <span>Foreign Amount:</span>
                <span id="calc-foreign">0.00</span>
            </div>
            <div class="calculation-row">
                <span>Exchange Rate:</span>
                <span id="calc-rate">0.000000</span>
            </div>
            <div class="calculation-row">
                <span>Total (MYR):</span>
                <span id="calc-total">RM 0.00</span>
            </div>
        </div>

        <!-- Compliance Warning -->
        <div id="compliance-warning" class="compliance-warning" style="display: none;">
            <h4>⚠️ Compliance Alert</h4>
            <p>This transaction exceeds RM 50,000 and will require manager approval.</p>
        </div>

        <!-- Stock Info for Sell -->
        <div id="stock-info" class="stock-info" style="display: none;">
            <h4>📦 Current Stock</h4>
            <p>Available: <span id="stock-available">-</span></p>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="card">
        <h2>Additional Information</h2>
        <div class="form-grid">
            <div class="form-group">
                <label for="purpose">Purpose *</label>
                <select name="purpose" id="purpose" required>
                    <option value="">Select Purpose</option>
                    <option value="Travel" {{ old('purpose') == 'Travel' ? 'selected' : '' }}>Travel</option>
                    <option value="Business" {{ old('purpose') == 'Business' ? 'selected' : '' }}>Business</option>
                    <option value="Education" {{ old('purpose') == 'Education' ? 'selected' : '' }}>Education</option>
                    <option value="Family Support" {{ old('purpose') == 'Family Support' ? 'selected' : '' }}>Family Support</option>
                    <option value="Investment" {{ old('purpose') == 'Investment' ? 'selected' : '' }}>Investment</option>
                    <option value="Other" {{ old('purpose') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('purpose')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="source_of_funds">Source of Funds *</label>
                <select name="source_of_funds" id="source_of_funds" required>
                    <option value="">Select Source</option>
                    <option value="Salary" {{ old('source_of_funds') == 'Salary' ? 'selected' : '' }}>Salary</option>
                    <option value="Savings" {{ old('source_of_funds') == 'Savings' ? 'selected' : '' }}>Savings</option>
                    <option value="Business Income" {{ old('source_of_funds') == 'Business Income' ? 'selected' : '' }}>Business Income</option>
                    <option value="Loan" {{ old('source_of_funds') == 'Loan' ? 'selected' : '' }}>Loan</option>
                    <option value="Gift" {{ old('source_of_funds') == 'Gift' ? 'selected' : '' }}>Gift</option>
                    <option value="Other" {{ old('source_of_funds') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('source_of_funds')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="actions">
        <a href="/transactions" class="btn" style="background: #e2e8f0; color: #4a5568;">Cancel</a>
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
