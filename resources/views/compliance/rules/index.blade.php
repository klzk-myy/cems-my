@extends('layouts.app')

@section('title', 'AML Rules - CEMS-MY')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">AML Rules Engine</h1>
        <p class="page-header__subtitle">Configure Anti-Money Laundering rules for transaction monitoring</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('compliance.rules.create') }}" class="btn btn--primary">+ Create Rule</a>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card">
        <div class="stat-card__value">{{ $rules->total() }}</div>
        <div class="stat-card__label">Total Rules</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $rules->where('is_active', true)->count() }}</div>
        <div class="stat-card__label">Active</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $rules->where('is_active', false)->count() }}</div>
        <div class="stat-card__label">Inactive</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">{{ $rules->sum('risk_score') }}</div>
        <div class="stat-card__label">Max Risk Score</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-6">
    <div class="flex gap-4 items-center p-4">
        <label class="text-sm font-semibold text-gray-600">Rule Type:</label>
        <select onchange="window.location.href='?type='+this.value" class="form-select" style="max-width: 200px;">
            <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Types</option>
            @foreach($ruleTypes as $type)
                <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        @if(request('type'))
            <a href="{{ route('compliance.rules.index') }}" class="btn btn--secondary btn--sm">Clear Filter</a>
        @endif
    </div>
</div>

<div class="card">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">AML Rules</h3>

    @if($rules->count() > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Rule Name</th>
                <th>Type</th>
                <th>Action</th>
                <th>Risk Score</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rules as $rule)
            <tr>
                <td><strong class="text-gray-800">{{ $rule->rule_code }}</strong></td>
                <td class="text-gray-600">{{ $rule->rule_name }}</td>
                <td>
                    @php
                        $ruleTypeValue = is_object($rule->rule_type) ? $rule->rule_type->value : $rule->rule_type;
                        $ruleTypeBadgeClass = match($ruleTypeValue) {
                            'velocity' => 'status-badge--active',
                            'structuring' => 'status-badge--flagged',
                            'amount_threshold' => 'status-badge--pending',
                            'frequency' => 'status-badge--active',
                            'geographic' => 'status-badge--flagged',
                            default => 'status-badge--inactive'
                        };
                    @endphp
                    <span class="status-badge {{ $ruleTypeBadgeClass }}">
                        {{ is_object($rule->rule_type) ? $rule->rule_type->label() : (AmlRuleType::tryFrom($rule->rule_type)?->label() ?? 'Unknown') }}
                    </span>
                </td>
                <td>
                    @php
                        $actionBadgeClass = match($rule->action) {
                            'flag' => 'status-badge--pending',
                            'hold' => 'status-badge--flagged',
                            'block' => 'status-badge--danger',
                            default => 'status-badge--inactive'
                        };
                    @endphp
                    <span class="status-badge {{ $actionBadgeClass }}">
                        {{ ucfirst($rule->action) }}
                    </span>
                </td>
                <td class="text-gray-600">{{ $rule->risk_score }}</td>
                <td>
                    <span class="status-badge {{ $rule->is_active ? 'status-badge--active' : 'status-badge--inactive' }}">
                        {{ $rule->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-gray-500">{{ $rule->created_at->format('Y-m-d') }}</td>
                <td>
                    <div class="flex gap-2">
                        <a href="{{ route('compliance.rules.show', $rule) }}" class="btn btn--primary btn--sm">View</a>
                        <a href="{{ route('compliance.rules.edit', $rule) }}" class="btn btn--warning btn--sm">Edit</a>
                        <form action="{{ route('compliance.rules.toggle', $rule) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn--sm {{ $rule->is_active ? 'btn--danger' : 'btn--success' }}">
                                {{ $rule->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $rules->appends(request()->query())->links() }}
    </div>
    @else
    <div class="text-center py-12">
        <div class="text-5xl mb-4 text-gray-300">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">No AML Rules Configured</h3>
        <p class="text-gray-500 mb-6">No AML rules have been created yet. Create your first rule to start monitoring transactions.</p>
        <a href="{{ route('compliance.rules.create') }}" class="btn btn--primary">+ Create Rule</a>
    </div>
    @endif
</div>
@endsection
