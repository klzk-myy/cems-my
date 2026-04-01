@extends('layouts.app')

@section('title', 'Stock & Cash Management - CEMS-MY')

@section('styles')
<style>
    .stock-cash-header {
        margin-bottom: 1.5rem;
    }
    .stock-cash-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .stock-cash-header p {
        color: #718096;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        padding: 1.5rem;
        color: white;
        text-align: center;
    }
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
    }
    .stat-label {
        margin-top: 0.5rem;
        opacity: 0.9;
    }

    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
    }

    .pnl-positive { color: #38a169; }
    .pnl-negative { color: #e53e3e; }
</style>
@endsection

@section('content')
<div class="stock-cash-header">
    <h2>Stock & Cash Management</h2>
    <p>Manage foreign currency inventory and till operations</p>
</div>

<!-- Statistics -->
<div class="grid">
    <div class="stat-card">
        <div class="stat-value">{{ $stats['total_currencies'] ?? 0 }}</div>
        <div class="stat-label">Active Currencies</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $stats['active_positions'] ?? 0 }}</div>
        <div class="stat-label">Currency Positions</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $stats['open_tills'] ?? 0 }}</div>
        <div class="stat-label">Open Tills</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ number_format($stats['total_variance'] ?? 0, 2) }}</div>
        <div class="stat-label">Total Variance (MYR)</div>
    </div>
</div>

<!-- Till Operations -->
<div class="card">
    <h2>Till Operations</h2>

    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
        <form action="/stock-cash/open" method="POST" style="flex: 1;">
            @csrf
            <h3 style="margin-bottom: 1rem; color: #2d3748;">Open Till</h3>
            <div class="form-group">
                <label>Till ID</label>
                <input type="text" name="till_id" placeholder="e.g., TILL-001" required>
            </div>
            <div class="form-group">
                <label>Currency</label>
                <select name="currency_code" required>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Opening Balance</label>
                <input type="number" step="0.0001" name="opening_balance" required>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" placeholder="Optional">
            </div>
            <button type="submit" class="btn btn-success">Open Till</button>
        </form>

        <form action="/stock-cash/close" method="POST" style="flex: 1;">
            @csrf
            <h3 style="margin-bottom: 1rem; color: #2d3748;">Close Till</h3>
            <div class="form-group">
                <label>Till ID</label>
                <input type="text" name="till_id" placeholder="e.g., TILL-001" required>
            </div>
            <div class="form-group">
                <label>Currency</label>
                <select name="currency_code" required>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->code }}">{{ $currency->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Closing Balance</label>
                <input type="number" step="0.0001" name="closing_balance" required>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" placeholder="Optional">
            </div>
            <button type="submit" class="btn btn-warning">Close Till</button>
        </form>
    </div>
</div>

<!-- Currency Positions -->
<div class="card">
    <h2>Currency Positions</h2>

    @if(count($positions) > 0)
        <table>
            <thead>
                <tr>
                    <th>Currency</th>
                    <th>Till</th>
                    <th>Balance</th>
                    <th>Avg Cost Rate</th>
                    <th>Last Valuation</th>
                    <th>Unrealized P&L</th>
                </tr>
            </thead>
            <tbody>
                @foreach($positions as $position)
                <tr>
                    <td><strong>{{ $position->currency_code }}</strong></td>
                    <td>{{ $position->till_id }}</td>
                    <td>{{ number_format($position->balance, 4) }}</td>
                    <td>{{ number_format($position->avg_cost_rate, 6) }}</td>
                    <td>{{ $position->last_valuation_rate ? number_format($position->last_valuation_rate, 6) : 'N/A' }}</td>
                    <td class="{{ ($position->unrealized_pnl ?? 0) >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                        {{ $position->unrealized_pnl ? number_format($position->unrealized_pnl, 2) : '0.00' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 1rem; padding: 1rem; background: #f7fafc; border-radius: 4px;">
            <strong>Total Unrealized P&L: </strong>
            <span class="{{ ($totalPnl ?? 0) >= 0 ? 'pnl-positive' : 'pnl-negative' }}" style="font-size: 1.25rem;">
                MYR {{ number_format($totalPnl ?? 0, 2) }}
            </span>
        </div>
    @else
        <div class="alert alert-success">
            No currency positions recorded yet. Positions will be created automatically when transactions are processed.
        </div>
    @endif
</div>

<!-- Today's Till Balances -->
<div class="card">
    <h2>Today's Till Balances</h2>

    @if($todayBalances->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Till ID</th>
                    <th>Currency</th>
                    <th>Opening</th>
                    <th>Closing</th>
                    <th>Variance</th>
                    <th>Status</th>
                    <th>Opened By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($todayBalances as $balance)
                <tr>
                    <td>{{ $balance->till_id }}</td>
                    <td>{{ $balance->currency_code }}</td>
                    <td>{{ number_format($balance->opening_balance, 4) }}</td>
                    <td>{{ $balance->closing_balance ? number_format($balance->closing_balance, 4) : '-' }}</td>
                    <td class="{{ ($balance->variance ?? 0) == 0 ? 'pnl-positive' : 'pnl-negative' }}">
                        {{ $balance->variance ? number_format($balance->variance, 2) : '-' }}
                    </td>
                    <td>
                        @if($balance->closed_at)
                            <span class="pnl-positive">Closed</span>
                        @else
                            <span class="pnl-negative">Open</span>
                        @endif
                    </td>
                    <td>{{ $balance->opener->username ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-success">
            No tills opened today. Use the "Open Till" form above to get started.
        </div>
    @endif
</div>
@endsection
