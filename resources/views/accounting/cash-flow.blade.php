@extends('layouts.app')

@section('title', 'Cash Flow Statement - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item">
            <a href="{{ route('accounting.index') }}" class="breadcrumbs__link">Accounting</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Cash Flow</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="accounting-header">
    <h2>Cash Flow Statement</h2>
    <p>Direct method cash flow analysis</p>
</div>

<div class="card">
    <div class="card-header">
        <h4>Select Period</h4>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.cash-flow') }}">
            <div style="display: flex; gap: 1rem; align-items: flex-end;">
                <div>
                    <label for="from_date">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}" class="form-control">
                </div>
                <div>
                    <label for="to_date">To Date</label>
                    <input type="date" name="to_date" id="to_date" value="{{ $toDate }}" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
        </form>
    </div>
</div>

@if(isset($cashFlow))
<div class="card">
    <div class="card-header">
        <h4>Cash Flow Statement</h4>
        <p class="text-muted mb-0">{{ $fromDate }} to {{ $toDate }}</p>
    </div>
    <div class="card-body">
        <!-- Operating Activities -->
        <h5 class="text-primary">Operating Activities</h5>
        <table class="table">
            <tbody>
                <tr>
                    <td>Cash Received from Customers</td>
                    <td style="text-align: right;">{{ number_format((float) $cashFlow['operating']['cash_from_customers'], 2) }}</td>
                </tr>
                <tr>
                    <td>Cash Paid to Suppliers</td>
                    <td style="text-align: right;">( {{ number_format((float) $cashFlow['operating']['cash_paid_to_suppliers'], 2) }} )</td>
                </tr>
                <tr>
                    <td>Cash Paid for Salaries</td>
                    <td style="text-align: right;">( {{ number_format((float) $cashFlow['operating']['cash_paid_for_salaries'], 2) }} )</td>
                </tr>
                <tr>
                    <td>Cash Paid for Other Expenses</td>
                    <td style="text-align: right;">( {{ number_format((float) $cashFlow['operating']['cash_paid_for_expenses'], 2) }} )</td>
                </tr>
                <tr class="table-light">
                    <td><strong>Net Cash from Operating Activities</strong></td>
                    <td style="text-align: right;">
                        <strong class="{{ (float) $cashFlow['operating']['net_operating'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                            {{ number_format((float) $cashFlow['operating']['net_operating'], 2) }}
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Investing Activities -->
        <h5 class="text-primary mt-4">Investing Activities</h5>
        <table class="table">
            <tbody>
                <tr>
                    <td>Asset Purchases</td>
                    <td style="text-align: right;">( {{ number_format((float) $cashFlow['investing']['asset_purchases'], 2) }} )</td>
                </tr>
                <tr>
                    <td>Asset Sales</td>
                    <td style="text-align: right;">{{ number_format((float) $cashFlow['investing']['asset_sales'], 2) }}</td>
                </tr>
                <tr>
                    <td>Investment Income</td>
                    <td style="text-align: right;">{{ number_format((float) $cashFlow['investing']['investment_income'], 2) }}</td>
                </tr>
                <tr class="table-light">
                    <td><strong>Net Cash from Investing Activities</strong></td>
                    <td style="text-align: right;">
                        <strong class="{{ (float) $cashFlow['investing']['net_investing'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                            {{ number_format((float) $cashFlow['investing']['net_investing'], 2) }}
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Financing Activities -->
        <h5 class="text-primary mt-4">Financing Activities</h5>
        <table class="table">
            <tbody>
                <tr>
                    <td>Loans Received</td>
                    <td style="text-align: right;">{{ number_format((float) $cashFlow['financing']['loans_received'], 2) }}</td>
                </tr>
                <tr>
                    <td>Loan Repayments</td>
                    <td style="text-align: right;">( {{ number_format((float) $cashFlow['financing']['loan_repayments'], 2) }} )</td>
                </tr>
                <tr>
                    <td>Dividends Paid</td>
                    <td style="text-align: right;">( {{ number_format((float) $cashFlow['financing']['dividends_paid'], 2) }} )</td>
                </tr>
                <tr class="table-light">
                    <td><strong>Net Cash from Financing Activities</strong></td>
                    <td style="text-align: right;">
                        <strong class="{{ (float) $cashFlow['financing']['net_financing'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                            {{ number_format((float) $cashFlow['financing']['net_financing'], 2) }}
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Summary -->
        <h5 class="text-primary mt-4">Cash Summary</h5>
        <table class="table">
            <tbody>
                <tr>
                    <td>Opening Cash Balance</td>
                    <td style="text-align: right;">{{ number_format((float) $cashFlow['opening_balance'], 2) }}</td>
                </tr>
                <tr>
                    <td>Net Change in Cash</td>
                    <td style="text-align: right;">
                        <span class="{{ (float) $cashFlow['net_change'] >= 0 ? 'pnl-positive' : 'pnl-negative' }}">
                            {{ (float) $cashFlow['net_change'] >= 0 ? '+' : '' }}{{ number_format((float) $cashFlow['net_change'], 2) }}
                        </span>
                    </td>
                </tr>
                <tr class="table-success">
                    <td><strong>Closing Cash Balance</strong></td>
                    <td style="text-align: right;">
                        <strong>{{ number_format((float) $cashFlow['closing_balance'], 2) }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="card-body">
        <p class="text-muted">Select a date range and click "Generate Report" to view the cash flow statement.</p>
    </div>
</div>
@endif
@endsection
