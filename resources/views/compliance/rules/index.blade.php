@extends('layouts.app')

@section('title', 'AML Rules - CEMS-MY')

@section('styles')
<style>
    .rules-header {
        margin-bottom: 1.5rem;
    }
    .rules-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .rules-header p {
        color: #718096;
    }

    .summary-box {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
    }
    .summary-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1a365d;
    }
    .summary-label {
        color: #718096;
        margin-top: 0.5rem;
        font-size: 0.875rem;
    }
    .summary-value.active { color: #38a169; }
    .summary-value.inactive { color: #e53e3e; }

    .filter-bar {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-velocity { background: #ebf8ff; color: #2b6cb0; }
    .badge-structuring { background: #feebc8; color: #c05621; }
    .badge-amount_threshold { background: #faf5ff; color: #6b46c1; }
    .badge-frequency { background: #e6fffa; color: #319795; }
    .badge-geographic { background: #fff5f5; color: #c53030; }

    .badge-action-flag { background: #fef3c7; color: #d97706; }
    .badge-action-hold { background: #fee2e2; color: #dc2626; }
    .badge-action-block { background: #7f1d1d; color: #ffffff; }

    .badge-active { background: #c6f6d5; color: #276749; }
    .badge-inactive { background: #fed7d7; color: #c53030; }

    .action-btns {
        display: flex;
        gap: 0.5rem;
    }

    .btn-create {
        background: #3182ce;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
        border: none;
        cursor: pointer;
    }
    .btn-create:hover {
        background: #2c5282;
    }

    .btn-view {
        background: #3182ce;
        color: white;
        padding: 0.375rem 0.75rem;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .btn-view:hover {
        background: #2c5282;
    }

    .btn-edit {
        background: #dd6b20;
        color: white;
        padding: 0.375rem 0.75rem;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .btn-edit:hover {
        background: #c05621;
    }

    .btn-toggle {
        padding: 0.375rem 0.75rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    .btn-toggle.activate {
        background: #38a169;
        color: white;
    }
    .btn-toggle.deactivate {
        background: #e53e3e;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #718096;
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
        gap: 0.25rem;
    }
    .pagination a,
    .pagination span {
        padding: 0.5rem 0.75rem;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.875rem;
    }
    .pagination a {
        background: #edf2f7;
        color: #4a5568;
    }
    .pagination a:hover {
        background: #e2e8f0;
    }
    .pagination span {
        background: #3182ce;
        color: white;
    }
</style>
@endsection

@section('content')
<div class="rules-header">
    <div class="flex justify-between items-center">
        <div>
            <h2>AML Rules Engine</h2>
            <p>Configure Anti-Money Laundering rules for transaction monitoring</p>
        </div>
        <a href="{{ route('compliance.rules.create') }}" class="btn-create">+ Create Rule</a>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid">
    <div class="summary-box">
        <div class="summary-value">{{ $rules->total() }}</div>
        <div class="summary-label">Total Rules</div>
    </div>
    <div class="summary-box">
        <div class="summary-value active">{{ $rules->where('is_active', true)->count() }}</div>
        <div class="summary-label">Active</div>
    </div>
    <div class="summary-box">
        <div class="summary-value inactive">{{ $rules->where('is_active', false)->count() }}</div>
        <div class="summary-label">Inactive</div>
    </div>
    <div class="summary-box">
        <div class="summary-value">{{ $rules->sum('risk_score') }}</div>
        <div class="summary-label">Max Risk Score</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <label>Rule Type:</label>
    <select onchange="window.location.href='?type='+this.value">
        <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Types</option>
        @foreach($ruleTypes as $type)
            <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>
                {{ $type->label() }}
            </option>
        @endforeach
    </select>
    @if(request('type'))
        <a href="{{ route('compliance.rules.index') }}" class="btn btn-sm">Clear Filter</a>
    @endif
</div>

<!-- Rules Table -->
<div class="card">
    <h2>AML Rules</h2>

    @if($rules->count() > 0)
    <table>
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
                <td><strong>{{ $rule->rule_code }}</strong></td>
                <td>{{ $rule->rule_name }}</td>
                <td>
                    <span class="badge badge-{{ $rule->rule_type->value ?? 'unknown' }}">
                        {{ $rule->rule_type->label() ?? 'Unknown' }}
                    </span>
                </td>
                <td>
                    <span class="badge badge-action-{{ $rule->action }}">
                        {{ ucfirst($rule->action) }}
                    </span>
                </td>
                <td>{{ $rule->risk_score }}</td>
                <td>
                    <span class="badge {{ $rule->is_active ? 'badge-active' : 'badge-inactive' }}">
                        {{ $rule->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>{{ $rule->created_at->format('Y-m-d') }}</td>
                <td>
                    <div class="action-btns">
                        <a href="{{ route('compliance.rules.show', $rule) }}" class="btn-view">View</a>
                        <a href="{{ route('compliance.rules.edit', $rule) }}" class="btn-edit">Edit</a>
                        <form action="{{ route('compliance.rules.toggle', $rule) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-toggle {{ $rule->is_active ? 'deactivate' : 'activate' }}">
                                {{ $rule->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination">
        {{ $rules->appends(request()->query())->links() }}
    </div>
    @else
    <div class="empty-state">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🛡️</div>
        <h3>No AML Rules Configured</h3>
        <p>No AML rules have been created yet. Create your first rule to start monitoring transactions.</p>
        <br>
        <a href="{{ route('compliance.rules.create') }}" class="btn-create">+ Create Rule</a>
    </div>
    @endif
</div>
@endsection
