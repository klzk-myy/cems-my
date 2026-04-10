@extends('layouts.app')

@section('title', 'Edit AML Rule - CEMS-MY')

@section('content')
<a href="{{ route('compliance.rules.index') }}" class="inline-block px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold text-sm hover:bg-gray-300 transition-colors mb-4">← Back to Rules</a>

<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Edit AML Rule</h2>
    <p class="text-gray-500 text-sm">Update the Anti-Money Laundering rule configuration</p>
</div>

@if($errors->any())
<div class="p-4 mb-6 rounded bg-red-100 text-red-800 border border-red-300">
    <strong>Please fix the following errors:</strong>
    <ul class="mt-2 list-disc list-inside">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('compliance.rules.update', $rule) }}" method="POST" class="bg-white rounded-lg shadow-sm p-6">
    @csrf
    @method('PUT')

    <div class="mb-6">
        <label class="block mb-2 text-sm font-semibold text-gray-700">Rule Type <span class="text-red-600">*</span></label>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @foreach($ruleTypeOptions as $value => $option)
            <label class="rule-type-option border-2 border-gray-200 rounded-lg p-4 cursor-pointer transition-all hover:border-blue-500" data-value="{{ $value }}">
                <input type="radio" name="rule_type" value="{{ $value }}"
                    {{ old('rule_type', is_object($rule->rule_type) ? $rule->rule_type->value : $rule->rule_type) == $value ? 'checked' : '' }}
                    onchange="updateConditionSchema('{{ $value }}')">
                <input type="radio" name="rule_type" value="{{ $value }}" class="hidden">
                <h4 class="text-gray-800 font-semibold mb-1">{{ $option['label'] }}</h4>
                <p class="text-xs text-gray-500 m-0">{{ $option['description'] }}</p>
            </label>
            @endforeach
        </div>
    </div>

    <div class="mb-6">
        <label for="rule_code" class="block mb-1 text-sm font-semibold text-gray-700">Rule Code <span class="text-red-600">*</span></label>
        <input type="text" id="rule_code" name="rule_code"
            class="w-full p-3 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            value="{{ old('rule_code', $rule->rule_code) }}" placeholder="e.g., VEL-001"
            maxlength="50" required>
        <p class="text-xs text-gray-500 mt-1">Unique identifier for this rule (max 50 characters)</p>
    </div>

    <div class="mb-6">
        <label for="rule_name" class="block mb-1 text-sm font-semibold text-gray-700">Rule Name <span class="text-red-600">*</span></label>
        <input type="text" id="rule_name" name="rule_name"
            class="w-full p-3 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            value="{{ old('rule_name', $rule->rule_name) }}" placeholder="e.g., High Velocity Alert"
            maxlength="100" required>
    </div>

    <div class="mb-6">
        <label for="description" class="block mb-1 text-sm font-semibold text-gray-700">Description</label>
        <textarea id="description" name="description"
            class="w-full p-3 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 min-h-[100px] resize-y"
            placeholder="Describe what this rule detects and why...">{{ old('description', $rule->description) }}</textarea>
    </div>

    <div class="mb-6">
        <label for="action" class="block mb-1 text-sm font-semibold text-gray-700">Action When Triggered <span class="text-red-600">*</span></label>
        <select id="action" name="action"
            class="w-full p-3 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 cursor-pointer"
            required>
            <option value="flag" {{ old('action', $rule->action) == 'flag' ? 'selected' : '' }}>Flag - Create alert for review</option>
            <option value="hold" {{ old('action', $rule->action) == 'hold' ? 'selected' : '' }}>Hold - Put transaction on hold</option>
            <option value="block" {{ old('action', $rule->action) == 'block' ? 'selected' : '' }}>Block - Reject the transaction</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">
            <strong>Flag:</strong> Creates a flagged transaction for compliance review<br>
            <strong>Hold:</strong> Sets transaction status to OnHold pending review<br>
            <strong>Block:</strong> Immediately cancels the transaction
        </p>
    </div>

    <div class="mb-6">
        <label for="risk_score" class="block mb-1 text-sm font-semibold text-gray-700">Risk Score (0-100) <span class="text-red-600">*</span></label>
        <input type="number" id="risk_score" name="risk_score"
            class="w-full p-3 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            value="{{ old('risk_score', $rule->risk_score) }}" min="0" max="100" required>
        <p class="text-xs text-gray-500 mt-1">Higher scores indicate more serious compliance concerns</p>
    </div>

    <div class="mb-6">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $rule->is_active) ? 'checked' : '' }}
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <span class="text-sm text-gray-700">Active (rule will be evaluated against transactions)</span>
        </label>
    </div>

    <div class="mb-6">
        <label class="block mb-1 text-sm font-semibold text-gray-700">Conditions <span class="text-red-600">*</span></label>
        <p class="text-xs text-gray-500 mb-4">Configure the parameters for this rule based on the selected rule type</p>

        <div class="bg-gray-50 rounded-lg p-6" id="conditions-builder">
            <!-- Dynamic condition fields will be inserted here -->
        </div>

        <div class="mt-2">
            <label class="block mb-1 text-xs font-medium text-gray-600">Conditions JSON Preview:</label>
            <pre class="bg-gray-800 text-gray-200 p-4 rounded font-mono text-xs whitespace-pre-wrap mt-1" id="conditions-preview">{}</pre>
        </div>
        <input type="hidden" name="conditions" id="conditions-json" value="{{ old('conditions', json_encode($rule->conditions ?? [])) }}">
    </div>

    <div class="flex gap-4 pt-4 mt-6 border-t border-gray-200">
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded font-semibold hover:bg-blue-700 transition-colors">Update Rule</button>
        <a href="{{ route('compliance.rules.show', $rule) }}" class="px-6 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold hover:bg-gray-300 transition-colors">Cancel</a>
    </div>
</form>

<script>
const ruleTypeSchemas = @json($ruleTypeOptions);
const currentConditions = @json($rule->conditions ?? []);

function updateConditionSchema(ruleType) {
    const schema = ruleTypeSchemas[ruleType];
    if (!schema) return;

    const builder = document.getElementById('conditions-builder');
    const preview = document.getElementById('conditions-preview');

    let html = '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">';

    switch(ruleType) {
        case 'velocity':
            html += `
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Window (hours)</label>
                    <input type="number" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_window_hours"
                        value="${currentConditions.window_hours || schema.default_conditions.window_hours || 24}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Max Transactions</label>
                    <input type="number" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_max_transactions"
                        value="${currentConditions.max_transactions || schema.default_conditions.max_transactions || 10}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Cumulative Threshold (MYR)</label>
                    <input type="number" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_cumulative_threshold"
                        value="${currentConditions.cumulative_threshold || schema.default_conditions.cumulative_threshold || ''}" placeholder="Optional"
                        onchange="updateConditionsPreview()">
                </div>
            `;
            break;

        case 'structuring':
            html += `
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Window (days)</label>
                    <input type="number" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_window_days"
                        value="${currentConditions.window_days || schema.default_conditions.window_days || 1}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Min Transaction Count</label>
                    <input type="number" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_min_transaction_count"
                        value="${currentConditions.min_transaction_count || schema.default_conditions.min_transaction_count || 3}" min="2"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Aggregate Threshold (MYR)</label>
                    <input type="number" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_aggregate_threshold"
                        value="${currentConditions.aggregate_threshold || schema.default_conditions.aggregate_threshold || 50000}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
            `;
            break;

        case 'amount_threshold':
            html += `
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Minimum Amount (MYR)</label>
                    <input type="number" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_min_amount"
                        value="${currentConditions.min_amount || schema.default_conditions.min_amount || 50000}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Currency</label>
                    <select class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500 cursor-pointer" id="cond_currency" onchange="updateConditionsPreview()">
                        <option value="MYR" ${(currentConditions.currency || schema.default_conditions.currency) === 'MYR' ? 'selected' : ''}>MYR</option>
                        <option value="USD" ${(currentConditions.currency || schema.default_conditions.currency) === 'USD' ? 'selected' : ''}>USD</option>
                        <option value="EUR" ${(currentConditions.currency || schema.default_conditions.currency) === 'EUR' ? 'selected' : ''}>EUR</option>
                        <option value="GBP" ${(currentConditions.currency || schema.default_conditions.currency) === 'GBP' ? 'selected' : ''}>GBP</option>
                        <option value="SGD" ${(currentConditions.currency || schema.default_conditions.currency) === 'SGD' ? 'selected' : ''}>SGD</option>
                    </select>
                </div>
            `;
            break;

        case 'frequency':
            html += `
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Window (hours)</label>
                    <input type="number" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_window_hours"
                        value="${currentConditions.window_hours || schema.default_conditions.window_hours || 1}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Max Transactions</label>
                    <input type="number" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_max_transactions"
                        value="${currentConditions.max_transactions || schema.default_conditions.max_transactions || 10}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
            `;
            break;

        case 'geographic':
            const countries = currentConditions.countries || schema.default_conditions.countries || [];
            html += `
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">High-Risk Countries (ISO codes, comma-separated)</label>
                    <input type="text" class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500" id="cond_countries"
                        value="${countries.join(', ')}"
                        placeholder="e.g., IR, KP, SY"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Match Field</label>
                    <select class="w-full p-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500 cursor-pointer" id="cond_match_field" onchange="updateConditionsPreview()">
                        <option value="customer_nationality" ${(currentConditions.match_field || schema.default_conditions.match_field) === 'customer_nationality' ? 'selected' : ''}>
                            Customer Nationality
                        </option>
                    </select>
                </div>
            `;
            break;
    }

    html += '</div>';
    builder.innerHTML = html;

    // Update selected state
    document.querySelectorAll('.rule-type-option').forEach(opt => {
        opt.classList.remove('border-blue-500', 'bg-blue-50');
        opt.classList.add('border-gray-200');
        if (opt.dataset.value === ruleType) {
            opt.classList.remove('border-gray-200');
            opt.classList.add('border-blue-500', 'bg-blue-50');
        }
    });

    updateConditionsPreview();
}

function updateConditionsPreview() {
    const selectedType = document.querySelector('input[name="rule_type"]:checked');
    if (!selectedType) return;

    const ruleType = selectedType.value;
    const conditions = {};

    switch(ruleType) {
        case 'velocity':
            conditions.window_hours = parseInt(document.getElementById('cond_window_hours')?.value) || 24;
            conditions.max_transactions = parseInt(document.getElementById('cond_max_transactions')?.value) || 10;
            const cumThreshold = document.getElementById('cond_cumulative_threshold')?.value;
            if (cumThreshold) conditions.cumulative_threshold = parseFloat(cumThreshold);
            break;

        case 'structuring':
            conditions.window_days = parseInt(document.getElementById('cond_window_days')?.value) || 1;
            conditions.min_transaction_count = parseInt(document.getElementById('cond_min_transaction_count')?.value) || 3;
            conditions.aggregate_threshold = parseFloat(document.getElementById('cond_aggregate_threshold')?.value) || 50000;
            break;

        case 'amount_threshold':
            conditions.min_amount = parseFloat(document.getElementById('cond_min_amount')?.value) || 50000;
            conditions.currency = document.getElementById('cond_currency')?.value || 'MYR';
            break;

        case 'frequency':
            conditions.window_hours = parseInt(document.getElementById('cond_window_hours')?.value) || 1;
            conditions.max_transactions = parseInt(document.getElementById('cond_max_transactions')?.value) || 10;
            break;

        case 'geographic':
            const countriesStr = document.getElementById('cond_countries')?.value || '';
            conditions.countries = countriesStr.split(',').map(c => c.trim().toUpperCase()).filter(c => c.length === 2);
            conditions.match_field = document.getElementById('cond_match_field')?.value || 'customer_nationality';
            break;
    }

    document.getElementById('conditions-preview').textContent = JSON.stringify(conditions, null, 2);
    document.getElementById('conditions-json').value = JSON.stringify(conditions);
}

// Initialize on page load with current rule type
document.addEventListener('DOMContentLoaded', function() {
    const currentType = '{{ is_object($rule->rule_type) ? $rule->rule_type->value : $rule->rule_type }}';
    if (currentType) {
        updateConditionSchema(currentType);
    }
});
</script>
@endsection
