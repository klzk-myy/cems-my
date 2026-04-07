@extends('layouts.app')

@section('title', 'Financial Ratios - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Financial Ratios Dashboard</h2>
    <p>Key performance indicators for financial analysis</p>
</div>

<div class="card">
    <div class="card-header">
        <h4>Select Period</h4>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.ratios') }}">
            <div style="display: flex; gap: 1rem; align-items: flex-end;">
                <div>
                    <label for="from_date">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}" class="form-control">
                </div>
                <div>
                    <label for="to_date">To Date</label>
                    <input type="date" name="to_date" id="to_date" value="{{ $toDate }}" class="form-control">
                </div>
                <div>
                    <label for="as_of_date">As of Date</label>
                    <input type="date" name="as_of_date" id="as_of_date" value="{{ $asOfDate }}" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Calculate Ratios</button>
            </div>
        </form>
    </div>
</div>

@if(isset($ratios))
<!-- Liquidity Ratios -->
<div class="card mt-4">
    <div class="card-header bg-primary text-white">
        <h4>Liquidity Ratios</h4>
        <p class="mb-0">Short-term solvency measures</p>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="ratio-card">
                    <h5>Current Ratio</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['liquidity']['current_ratio'], 2) }}</div>
                    <div class="ratio-formula">Current Assets / Current Liabilities</div>
                    <div class="ratio-interpretation">
                        @if((float) $ratios['liquidity']['current_ratio'] >= 1.5)
                            <span class="badge bg-success">Strong</span>
                        @elseif((float) $ratios['liquidity']['current_ratio'] >= 1)
                            <span class="badge bg-warning">Acceptable</span>
                        @else
                            <span class="badge bg-danger">Weak</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ratio-card">
                    <h5>Quick Ratio</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['liquidity']['quick_ratio'], 2) }}</div>
                    <div class="ratio-formula">(Current Assets - Inventory) / Current Liabilities</div>
                    <div class="ratio-interpretation">
                        @if((float) $ratios['liquidity']['quick_ratio'] >= 1)
                            <span class="badge bg-success">Strong</span>
                        @elseif((float) $ratios['liquidity']['quick_ratio'] >= 0.5)
                            <span class="badge bg-warning">Acceptable</span>
                        @else
                            <span class="badge bg-danger">Weak</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ratio-card">
                    <h5>Cash Ratio</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['liquidity']['cash_ratio'], 2) }}</div>
                    <div class="ratio-formula">Cash / Current Liabilities</div>
                    <div class="ratio-interpretation">
                        @if((float) $ratios['liquidity']['cash_ratio'] >= 0.5)
                            <span class="badge bg-success">Strong</span>
                        @elseif((float) $ratios['liquidity']['cash_ratio'] >= 0.2)
                            <span class="badge bg-warning">Acceptable</span>
                        @else
                            <span class="badge bg-danger">Weak</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <h6 class="mt-4">Components</h6>
        <table class="table table-sm">
            <tr>
                <td>Current Assets</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['liquidity']['current_assets'], 2) }}</td>
            </tr>
            <tr>
                <td>Current Liabilities</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['liquidity']['current_liabilities'], 2) }}</td>
            </tr>
            <tr>
                <td>Inventory</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['liquidity']['inventory'], 2) }}</td>
            </tr>
            <tr>
                <td>Cash</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['liquidity']['cash'], 2) }}</td>
            </tr>
        </table>
    </div>
</div>

<!-- Profitability Ratios -->
<div class="card mt-4">
    <div class="card-header bg-success text-white">
        <h4>Profitability Ratios</h4>
        <p class="mb-0">Earnings and return measures</p>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="ratio-card">
                    <h5>Gross Profit Margin</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['profitability']['gross_profit_margin'] * 100, 1) }}%</div>
                    <div class="ratio-formula">(Revenue - COGS) / Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ratio-card">
                    <h5>Net Profit Margin</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['profitability']['net_profit_margin'] * 100, 1) }}%</div>
                    <div class="ratio-formula">Net Income / Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ratio-card">
                    <h5>Return on Equity (ROE)</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['profitability']['roe'], 2) }}</div>
                    <div class="ratio-formula">Net Income / Equity</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ratio-card">
                    <h5>Return on Assets (ROA)</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['profitability']['roa'], 2) }}</div>
                    <div class="ratio-formula">Net Income / Total Assets</div>
                </div>
            </div>
        </div>

        <h6 class="mt-4">Components</h6>
        <table class="table table-sm">
            <tr>
                <td>Revenue</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['profitability']['revenue'], 2) }}</td>
            </tr>
            <tr>
                <td>COGS</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['profitability']['cogs'], 2) }}</td>
            </tr>
            <tr>
                <td>Gross Profit</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['profitability']['gross_profit'], 2) }}</td>
            </tr>
            <tr>
                <td>Net Income</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['profitability']['net_income'], 2) }}</td>
            </tr>
        </table>
    </div>
</div>

<!-- Leverage Ratios -->
<div class="card mt-4">
    <div class="card-header bg-warning text-dark">
        <h4>Leverage Ratios</h4>
        <p class="mb-0">Financial leverage measures</p>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="ratio-card">
                    <h5>Debt-to-Equity</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['leverage']['debt_to_equity'], 2) }}</div>
                    <div class="ratio-formula">Total Debt / Equity</div>
                    <div class="ratio-interpretation">
                        @if((float) $ratios['leverage']['debt_to_equity'] <= 1)
                            <span class="badge bg-success">Conservative</span>
                        @elseif((float) $ratios['leverage']['debt_to_equity'] <= 2)
                            <span class="badge bg-warning">Moderate</span>
                        @else
                            <span class="badge bg-danger">High Leverage</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="ratio-card">
                    <h5>Debt-to-Assets</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['leverage']['debt_to_assets'], 2) }}</div>
                    <div class="ratio-formula">Total Debt / Total Assets</div>
                    <div class="ratio-interpretation">
                        @if((float) $ratios['leverage']['debt_to_assets'] <= 0.5)
                            <span class="badge bg-success">Conservative</span>
                        @elseif((float) $ratios['leverage']['debt_to_assets'] <= 0.7)
                            <span class="badge bg-warning">Moderate</span>
                        @else
                            <span class="badge bg-danger">High Leverage</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <h6 class="mt-4">Components</h6>
        <table class="table table-sm">
            <tr>
                <td>Total Debt</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['leverage']['total_debt'], 2) }}</td>
            </tr>
            <tr>
                <td>Equity</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['leverage']['equity'], 2) }}</td>
            </tr>
            <tr>
                <td>Total Assets</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['leverage']['total_assets'], 2) }}</td>
            </tr>
        </table>
    </div>
</div>

<!-- Efficiency Ratios -->
<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <h4>Efficiency Ratios</h4>
        <p class="mb-0">Asset utilization measures</p>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="ratio-card">
                    <h5>Asset Turnover</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['efficiency']['asset_turnover'], 2) }}</div>
                    <div class="ratio-formula">Revenue / Total Assets</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="ratio-card">
                    <h5>Inventory Turnover</h5>
                    <div class="ratio-value">{{ number_format((float) $ratios['efficiency']['inventory_turnover'], 2) }}</div>
                    <div class="ratio-formula">COGS / Inventory</div>
                </div>
            </div>
        </div>

        <h6 class="mt-4">Components</h6>
        <table class="table table-sm">
            <tr>
                <td>Revenue</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['efficiency']['revenue'], 2) }}</td>
            </tr>
            <tr>
                <td>Total Assets</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['efficiency']['total_assets'], 2) }}</td>
            </tr>
            <tr>
                <td>COGS</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['efficiency']['cogs'], 2) }}</td>
            </tr>
            <tr>
                <td>Inventory</td>
                <td style="text-align: right;">{{ number_format((float) $ratios['efficiency']['inventory'], 2) }}</td>
            </tr>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="card-body">
        <p class="text-muted">Select date parameters and click "Calculate Ratios" to view financial ratios.</p>
    </div>
</div>
@endif

<style>
.ratio-card {
    padding: 1.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    text-align: center;
}
.ratio-card h5 {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}
.ratio-value {
    font-size: 2rem;
    font-weight: bold;
    color: #212529;
}
.ratio-formula {
    font-size: 0.75rem;
    color: #adb5bd;
    margin-top: 0.25rem;
}
.ratio-interpretation {
    margin-top: 0.5rem;
}
</style>
@endsection
