@extends('layouts.app')

@section('title', 'Budget vs Actual - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Budget vs Actual Report</h2>
    <p>Compare budgeted amounts with actual expenditures</p>
</div>

<div class="card">
    <h2>Select Period</h2>
    <form method="GET" action="{{ route('accounting.budget') }}">
        <div style="display: flex; gap: 1rem; align-items: flex-end;">
            <div>
                <label for="period">Accounting Period</label>
                <input type="month" name="period" id="period" value="{{ $periodCode }}" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">View Report</button>
        </div>
    </form>
</div>

@if(isset($report) && isset($report['items']) && count($report['items']) > 0)
<div class="card">
    <h2>Budget Report - {{ $report['period_code'] ?? $periodCode }}</h2>

    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th style="text-align: right;">Budget</th>
                <th style="text-align: right;">Actual</th>
                <th style="text-align: right;">Variance</th>
                <th style="text-align: right;">% Used</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['items'] as $item)
            <tr>
                <td><strong>{{ $item['account_code'] }}</strong></td>
                <td>{{ $item['account_name'] }}</td>
                <td style="text-align: right;">{{ number_format((float) $item['budget'], 2) }}</td>
                <td style="text-align: right;">{{ number_format((float) $item['actual'], 2) }}</td>
                <td style="text-align: right;" class="{{ (float) $item['variance'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                    {{ (float) $item['variance'] >= 0 ? '+' : '' }}{{ number_format((float) $item['variance'], 2) }}
                </td>
                <td style="text-align: right;">
                    @php
                        $percent = (float) $item['budget'] != 0 ? ((float) $item['actual'] / (float) $item['budget']) * 100 : 0;
                        $color = $percent > 100 ? '#e53e3e' : ($percent > 80 ? '#dd6b20' : '#38a169');
                    @endphp
                    <span style="color: {{ $color }};">{{ number_format($percent, 1) }}%</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(isset($report['total_budget']))
    <div class="budget-totals" style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #ddd; display: flex; gap: 2rem; justify-content: flex-end;">
        <div><strong>Total Budget:</strong> <span id="total_budget">{{ number_format((float) $report['total_budget'], 2) }}</span></div>
        <div><strong>Total Actual:</strong> <span id="total_actual">{{ number_format((float) $report['total_actual'], 2) }}</span></div>
        <div><strong>Total Variance:</strong> <span id="total_variance">{{ number_format((float) $report['total_variance'], 2) }}</span></div>
        <div><strong>Over Budget:</strong> <span id="over_budget_count">{{ $report['over_budget_count'] }}</span></div>
    </div>
    @endif
</div>
@else
<div class="card">
    <div class="alert alert-info">
        No budget data available for this period. Run <code>php artisan db:seed --class=BudgetSeeder</code> to create sample budgets.
    </div>
</div>
@endif

@if(isset($unbudgeted) && count($unbudgeted) > 0)
<div class="card">
    <h2 style="color: #dd6b20;">Accounts Without Budget</h2>
    <div class="alert alert-warning">
        The following accounts have transactions but no budget allocated for {{ $periodCode }}.
    </div>
    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Account Type</th>
                <th style="text-align: right;">Actual Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($unbudgeted as $account)
            <tr>
                <td>{{ $account->account_code }}</td>
                <td>{{ $account->account_name }}</td>
                <td>{{ $account->account_type }}</td>
                <td style="text-align: right;">{{ number_format((float) $account->actual_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
