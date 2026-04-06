@extends('layouts.app')

@section('title', 'Edit AML Rule - CEMS-MY')

@section('styles')
<style>
    .form-header {
        margin-bottom: 1.5rem;
    }
    .form-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .form-header p {
        color: #718096;
    }

    .form-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 0.5rem;
    }
    .form-group label span.required {
        color: #e53e3e;
    }
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    .form-control:focus {
        outline: none;
        border-color: #3182ce;
        box-shadow: 0 0 0 3px rgba(49,130,206,0.1);
    }
    .form-help {
        font-size: 0.75rem;
        color: #718096;
        margin-top: 0.25rem;
    }
    .form-error {
        font-size: 0.75rem;
        color: #e53e3e;
        margin-top: 0.25rem;
    }

    textarea.form-control {
        min-height: 100px;
    }

    select.form-control {
        cursor: pointer;
    }

    .conditions-builder {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
    }

    .condition-field {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .condition-field label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 0.25rem;
    }

    .btn-group {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .btn-submit {
        background: #3182ce;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        font-weight: 600;
        border: none;
        cursor: pointer;
    }
    .btn-submit:hover {
        background: #2c5282;
    }

    .btn-cancel {
        background: #e2e8f0;
        color: #4a5568;
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }
    .btn-cancel:hover {
        background: #cbd5e0;
    }

    .rule-type-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .rule-type-option {
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .rule-type-option:hover {
        border-color: #3182ce;
    }
    .rule-type-option.selected {
        border-color: #3182ce;
        background: #ebf8ff;
    }
    .rule-type-option input {
        display: none;
    }
    .rule-type-option h4 {
        margin: 0 0 0.5rem 0;
        color: #2d3748;
    }
    .rule-type-option p {
        margin: 0;
        font-size: 0.75rem;
        color: #718096;
    }

    .schema-preview {
        background: #2d3748;
        color: #e2e8f0;
        padding: 1rem;
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.75rem;
        white-space: pre-wrap;
        margin-top: 0.5rem;
    }

    .alert {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .alert-error {
        background: #fed7d7;
        color: #c53030;
        border: 1px solid #fc8181;
    }

    .btn-back {
        background: #e2e8f0;
        color: #4a5568;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 1rem;
    }
    .btn-back:hover {
        background: #cbd5e0;
    }
</style>
@endsection

@section('content')
<a href="{{ route('compliance.rules.index') }}" class="btn-back">← Back to Rules</a>

<div class="form-header">
    <h2>Edit AML Rule</h2>
    <p>Update the Anti-Money Laundering rule configuration</p>
</div>

@if($errors->any())
<div class="alert alert-error">
    <strong>Please fix the following errors:</strong>
    <ul>
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('compliance.rules.update', $rule) }}" method="POST" class="form-card">
    @csrf
    @method('PUT')

    <div class="form-group">
        <label>Rule Type <span class="required">*</span></label>
        <div class="rule-type-selector">
            @foreach($ruleTypeOptions as $value => $option)
            <label class="rule-type-option" data-value="{{ $value }}">
                <input type="radio" name="rule_type" value="{{ $value }}"
                    {{ old('rule_type', $rule->rule_type->value ?? '') == $value ? 'checked' : '' }}
                    onchange="updateConditionSchema('{{ $value }}')">
                <h4>{{ $option['label'] }}</h4>
                <p>{{ $option['description'] }}</p>
            </label>
            @endforeach
        </div>
    </div>

    <div class="form-group">
        <label for="rule_code">Rule Code <span class="required">*</span></label>
        <input type="text" id="rule_code" name="rule_code" class="form-control"
            value="{{ old('rule_code', $rule->rule_code) }}" placeholder="e.g., VEL-001"
            maxlength="50" required>
        <p class="form-help">Unique identifier for this rule (max 50 characters)</p>
    </div>

    <div class="form-group">
        <label for="rule_name">Rule Name <span class="required">*</span></label>
        <input type="text" id="rule_name" name="rule_name" class="form-control"
            value="{{ old('rule_name', $rule->rule_name) }}" placeholder="e.g., High Velocity Alert"
            maxlength="100" required>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control"
            placeholder="Describe what this rule detects and why...">{{ old('description', $rule->description) }}</textarea>
    </div>

    <div class="form-group">
        <label for="action">Action When Triggered <span class="required">*</span></label>
        <select id="action" name="action" class="form-control" required>
            <option value="flag" {{ old('action', $rule->action) == 'flag' ? 'selected' : '' }}>Flag - Create alert for review</option>
            <option value="hold" {{ old('action', $rule->action) == 'hold' ? 'selected' : '' }}>Hold - Put transaction on hold</option>
            <option value="block" {{ old('action', $rule->action) == 'block' ? 'selected' : '' }}>Block - Reject the transaction</option>
        </select>
        <p class="form-help">
            <strong>Flag:</strong> Creates a flagged transaction for compliance review<br>
            <strong>Hold:</strong> Sets transaction status to OnHold pending review<br>
            <strong>Block:</strong> Immediately cancels the transaction
        </p>
    </div>

    <div class="form-group">
        <label for="risk_score">Risk Score (0-100) <span class="required">*</span></label>
        <input type="number" id="risk_score" name="risk_score" class="form-control"
            value="{{ old('risk_score', $rule->risk_score) }}" min="0" max="100" required>
        <p class="form-help">Higher scores indicate more serious compliance concerns</p>
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $rule->is_active) ? 'checked' : '' }}>
            Active (rule will be evaluated against transactions)
        </label>
    </div>

    <div class="form-group">
        <label>Conditions <span class="required">*</span></label>
        <p class="form-help">Configure the parameters for this rule based on the selected rule type</p>

        <div class="conditions-builder" id="conditions-builder">
            <!-- Dynamic condition fields will be inserted here -->
        </div>

        <div style="margin-top: 0.5rem;">
            <label>Conditions JSON Preview:</label>
            <div class="schema-preview" id="conditions-preview">{}</div>
        </div>
        <input type="hidden" name="conditions" id="conditions-json" value="{{ old('conditions', json_encode($rule->conditions ?? [])) }}">
    </div>

    <div class="btn-group">
        <button type="submit" class="btn-submit">Update Rule</button>
        <a href="{{ route('compliance.rules.show', $rule) }}" class="btn-cancel">Cancel</a>
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

    let html = '<div class="condition-field">';

    switch(ruleType) {
        case 'velocity':
            html += `
                <div>
                    <label>Window (hours)</label>
                    <input type="number" class="form-control" id="cond_window_hours"
                        value="${currentConditions.window_hours || schema.default_conditions.window_hours || 24}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label>Max Transactions</label>
                    <input type="number" class="form-control" id="cond_max_transactions"
                        value="${currentConditions.max_transactions || schema.default_conditions.max_transactions || 10}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label>Cumulative Threshold (MYR)</label>
                    <input type="number" class="form-control" id="cond_cumulative_threshold"
                        value="${currentConditions.cumulative_threshold || schema.default_conditions.cumulative_threshold || ''}" placeholder="Optional"
                        onchange="updateConditionsPreview()">
                </div>
            `;
            break;

        case 'structuring':
            html += `
                <div>
                    <label>Window (days)</label>
                    <input type="number" class="form-control" id="cond_window_days"
                        value="${currentConditions.window_days || schema.default_conditions.window_days || 1}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label>Min Transaction Count</label>
                    <input type="number" class="form-control" id="cond_min_transaction_count"
                        value="${currentConditions.min_transaction_count || schema.default_conditions.min_transaction_count || 3}" min="2"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label>Aggregate Threshold (MYR)</label>
                    <input type="number" class="form-control" id="cond_aggregate_threshold"
                        value="${currentConditions.aggregate_threshold || schema.default_conditions.aggregate_threshold || 50000}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
            `;
            break;

        case 'amount_threshold':
            html += `
                <div>
                    <label>Minimum Amount (MYR)</label>
                    <input type="number" class="form-control" id="cond_min_amount"
                        value="${currentConditions.min_amount || schema.default_conditions.min_amount || 50000}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label>Currency</label>
                    <select class="form-control" id="cond_currency" onchange="updateConditionsPreview()">
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
                    <label>Window (hours)</label>
                    <input type="number" class="form-control" id="cond_window_hours"
                        value="${currentConditions.window_hours || schema.default_conditions.window_hours || 1}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label>Max Transactions</label>
                    <input type="number" class="form-control" id="cond_max_transactions"
                        value="${currentConditions.max_transactions || schema.default_conditions.max_transactions || 10}" min="1"
                        onchange="updateConditionsPreview()">
                </div>
            `;
            break;

        case 'geographic':
            const countries = currentConditions.countries || schema.default_conditions.countries || [];
            html += `
                <div>
                    <label>High-Risk Countries (ISO codes, comma-separated)</label>
                    <input type="text" class="form-control" id="cond_countries"
                        value="${countries.join(', ')}"
                        placeholder="e.g., IR, KP, SY"
                        onchange="updateConditionsPreview()">
                </div>
                <div>
                    <label>Match Field</label>
                    <select class="form-control" id="cond_match_field" onchange="updateConditionsPreview()">
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
        opt.classList.remove('selected');
        if (opt.dataset.value === ruleType) {
            opt.classList.add('selected');
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
    const currentType = '{{ $rule->rule_type->value ?? '' }}';
    if (currentType) {
        updateConditionSchema(currentType);
    }
});
</script>
@endsection
