@extends('layouts.app')

@section('title', 'AML Rule Details - CEMS-MY')

@section('styles')
<style>
    .detail-header {
        margin-bottom: 1.5rem;
    }
    .detail-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .detail-header p {
        color: #718096;
    }

    .detail-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .detail-row {
        display: flex;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .detail-row:last-child {
        border-bottom: none;
    }
    .detail-label {
        font-weight: 600;
        color: #4a5568;
        width: 200px;
        flex-shrink: 0;
    }
    .detail-value {
        color: #2d3748;
        flex: 1;
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

    .conditions-json {
        background: #2d3748;
        color: #e2e8f0;
        padding: 1rem;
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.875rem;
        white-space: pre-wrap;
        overflow-x: auto;
    }

    .btn-group {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .btn-edit {
        background: #3182ce;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }
    .btn-edit:hover {
        background: #2c5282;
    }

    .btn-toggle {
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
        display: inline-block;
    }
    .btn-toggle.activate {
        background: #38a169;
        color: white;
    }
    .btn-toggle.deactivate {
        background: #e53e3e;
        color: white;
    }

    .btn-delete {
        background: #e2e8f0;
        color: #4a5568;
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        border: none;
        cursor: pointer;
    }
    .btn-delete:hover {
        background: #cbd5e0;
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

    .hit-history {
        margin-top: 1rem;
    }

    .hit-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.875rem;
    }
    .hit-item:last-child {
        border-bottom: none;
    }
    .hit-time {
        color: #718096;
    }
    .hit-transaction {
        color: #3182ce;
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #718096;
    }

    .summary-stats {
        display: flex;
        gap: 2rem;
        margin-bottom: 1.5rem;
    }
    .stat-box {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1rem 1.5rem;
        text-align: center;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #1a365d;
    }
    .stat-label {
        font-size: 0.75rem;
        color: #718096;
        margin-top: 0.25rem;
    }
</style>
@endsection

@section('content')
<a href="{{ route('compliance.rules.index') }}" class="btn-back">← Back to Rules</a>

<div class="detail-header">
    <h2>AML Rule: {{ $rule->rule_code }}</h2>
    <p>{{ $rule->rule_name }}</p>
</div>

<div class="summary-stats">
    <div class="stat-box">
        <div class="stat-value">{{ $hitCount }}</div>
        <div class="stat-label">Triggers (30 days)</div>
    </div>
    <div class="stat-box">
        <div class="stat-value">{{ $rule->risk_score }}</div>
        <div class="stat-label">Risk Score</div>
    </div>
</div>

<div class="detail-card">
    <h3>Rule Details</h3>

    <div class="detail-row">
        <div class="detail-label">Rule Code</div>
        <div class="detail-value"><strong>{{ $rule->rule_code }}</strong></div>
    </div>

    <div class="detail-row">
        <div class="detail-label">Rule Name</div>
        <div class="detail-value">{{ $rule->rule_name }}</div>
    </div>

    <div class="detail-row">
        <div class="detail-label">Description</div>
        <div class="detail-value">{{ $rule->description ?? 'No description provided' }}</div>
    </div>

    <div class="detail-row">
        <div class="detail-label">Rule Type</div>
        <div class="detail-value">
            <span class="badge badge-{{ $rule->rule_type->value ?? 'unknown' }}">
                {{ $rule->rule_type->label() ?? 'Unknown' }}
            </span>
        </div>
    </div>

    <div class="detail-row">
        <div class="detail-label">Action</div>
        <div class="detail-value">
            <span class="badge badge-action-{{ $rule->action }}">
                {{ ucfirst($rule->action) }}
            </span>
        </div>
    </div>

    <div class="detail-row">
        <div class="detail-label">Risk Score</div>
        <div class="detail-value">{{ $rule->risk_score }}</div>
    </div>

    <div class="detail-row">
        <div class="detail-label">Status</div>
        <div class="detail-value">
            <span class="badge {{ $rule->is_active ? 'badge-active' : 'badge-inactive' }}">
                {{ $rule->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>

    <div class="detail-row">
        <div class="detail-label">Created By</div>
        <div class="detail-value">{{ $rule->creator->full_name ?? 'System' }}</div>
    </div>

    <div class="detail-row">
        <div class="detail-label">Created At</div>
        <div class="detail-value">{{ $rule->created_at->format('Y-m-d H:i:s') }}</div>
    </div>

    <div class="detail-row">
        <div class="detail-label">Updated At</div>
        <div class="detail-value">{{ $rule->updated_at->format('Y-m-d H:i:s') }}</div>
    </div>
</div>

<div class="detail-card">
    <h3>Conditions</h3>
    <pre class="conditions-json">{{ json_encode($rule->conditions, JSON_PRETTY_PRINT) }}</pre>
</div>

<div class="detail-card">
    <h3>Rule Actions</h3>
    <div class="btn-group">
        <a href="{{ route('compliance.rules.edit', $rule) }}" class="btn-edit">Edit Rule</a>
        <form action="{{ route('compliance.rules.toggle', $rule) }}" method="POST" style="display: inline;">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn-toggle {{ $rule->is_active ? 'deactivate' : 'activate' }}">
                {{ $rule->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
        <form action="{{ route('compliance.rules.destroy', $rule) }}" method="POST" style="display: inline;"
            onsubmit="return confirm('Are you sure you want to delete this rule?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-delete">Delete Rule</button>
        </form>
    </div>
</div>

<div class="detail-card">
    <h3>Recent Triggers (Last 50)</h3>
    @if($hitHistory->count() > 0)
    <div class="hit-history">
        @foreach($hitHistory as $hit)
        <div class="hit-item">
            <div>
                <span class="hit-transaction">
                    @if(isset($hit->entity_id) && $hit->entity_type === 'Transaction')
                        Transaction #{{ $hit->entity_id }}
                    @else
                        {{ $hit->entity_type ?? 'Unknown' }} #{{ $hit->entity_id ?? 'N/A' }}
                    @endif
                </span>
                <br>
                <small>{{ $hit->description ?? 'Rule triggered' }}</small>
            </div>
            <div class="hit-time">
                {{ $hit->created_at->format('Y-m-d H:i') }}
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <p>No triggers recorded for this rule in the last 30 days.</p>
    </div>
    @endif
</div>
@endsection
