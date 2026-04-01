@extends('layouts.app')

@section('title', 'Reports - CEMS-MY')

@section('styles')
<style>
    .reports-header {
        margin-bottom: 1.5rem;
    }
    .reports-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .reports-header p {
        color: #718096;
    }

    .report-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-left: 4px solid #3182ce;
    }
    .report-card h3 {
        color: #1a365d;
        margin-bottom: 0.5rem;
    }
    .report-card p {
        color: #718096;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }
</style>
@endsection

@section('content')
<div class="reports-header">
    <h2>Regulatory Reports</h2>
    <p>BNM and PDPA compliant reporting for Money Services Business</p>
</div>

<!-- Report Types -->
<div class="grid">
    <!-- LCTR Report -->
    <div class="report-card">
        <h3>LCTR (Large Cash Transaction Report)</h3>
        <p>Monthly submission for transactions ≥ RM 25,000. Required by BNM AML/CFT Policy.</p>
        <div style="font-size: 0.75rem; color: #718096; margin-bottom: 1rem;">
            <strong>Fields:</strong> Transaction ID, Customer ID, Amount, Currency, Type, Date
        </div>
        <a href="#" class="btn btn-primary">Generate LCTR Report</a>
        <a href="#" class="btn btn-success">Export CSV</a>
    </div>

    <!-- MSB(2) Report -->
    <div class="report-card" style="border-left-color: #38a169;">
        <h3>MSB(2) Daily Statistical Report</h3>
        <p>Daily aggregated transaction volumes by currency for BNM statistical reporting.</p>
        <div style="font-size: 0.75rem; color: #718096; margin-bottom: 1rem;">
            <strong>Format:</strong> Date, Currency, Buy Volume, Buy Count, Sell Volume, Sell Count
        </div>
        <a href="#" class="btn btn-primary">Generate MSB(2)</a>
        <a href="#" class="btn btn-success">Export CSV</a>
    </div>

    <!-- Compliance Report -->
    <div class="report-card" style="border-left-color: #dd6b20;">
        <h3>Compliance Summary</h3>
        <p>Flagged transactions, risk ratings, and suspicious activity reports.</p>
        <div style="font-size: 0.75rem; color: #718096; margin-bottom: 1rem;">
            <strong>Includes:</strong> Open flags, EDD triggers, Sanction matches
        </div>
        <a href="#" class="btn btn-primary">Generate Report</a>
        <a href="#" class="btn btn-success">Export PDF</a>
    </div>

    <!-- Accounting Report -->
    <div class="report-card" style="border-left-color: #805ad5;">
        <h3>Accounting & Revaluation</h3>
        <p>Monthly revaluation summary, P&L reports, and till reconciliation.</p>
        <div style="font-size: 0.75rem; color: #718096; margin-bottom: 1rem;">
            <strong>Includes:</strong> Currency positions, unrealized gains/losses
        </div>
        <a href="#" class="btn btn-primary">Generate Report</a>
        <a href="#" class="btn btn-success">Export CSV</a>
    </div>

    <!-- Customer Risk Report -->
    <div class="report-card" style="border-left-color: #e53e3e;">
        <h3>Customer Risk Distribution</h3>
        <p>Risk-based customer classification and PEP screening results.</p>
        <div style="font-size: 0.75rem; color: #718096; margin-bottom: 1rem;">
            <strong>Categories:</strong> Low, Medium, High risk customers
        </div>
        <a href="#" class="btn btn-primary">Generate Report</a>
        <a href="#" class="btn btn-success">Export CSV</a>
    </div>

    <!-- Audit Trail -->
    <div class="report-card" style="border-left-color: #4a5568;">
        <h3>Audit Trail</h3>
        <p>Complete system audit log for compliance review and investigation.</p>
        <div style="font-size: 0.75rem; color: #718096; margin-bottom: 1rem;">
            <strong>Retention:</strong> 7 years (PDPA 2024 compliant)
        </div>
        <a href="#" class="btn btn-primary">Generate Report</a>
        <a href="#" class="btn btn-success">Export CSV</a>
    </div>
</div>

<!-- Report Schedule -->
<div class="card">
    <h2>Scheduled Reports</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0;">
                <th style="text-align: left; padding: 0.75rem; color: #718096; font-weight: 600;">Report</th>
                <th style="text-align: left; padding: 0.75rem; color: #718096; font-weight: 600;">Frequency</th>
                <th style="text-align: left; padding: 0.75rem; color: #718096; font-weight: 600;">Deadline</th>
                <th style="text-align: left; padding: 0.75rem; color: #718096; font-weight: 600;">Recipient</th>
                <th style="text-align: left; padding: 0.75rem; color: #718096; font-weight: 600;">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 0.75rem;">LCTR</td>
                <td style="padding: 0.75rem;">Monthly</td>
                <td style="padding: 0.75rem;">5th of month</td>
                <td style="padding: 0.75rem;">BNM Compliance</td>
                <td style="padding: 0.75rem;"><span style="color: #38a169;">✓ Automated</span></td>
            </tr>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 0.75rem;">MSB(2)</td>
                <td style="padding: 0.75rem;">Daily</td>
                <td style="padding: 0.75rem;">Next day 9:00 AM</td>
                <td style="padding: 0.75rem;">BNM Statistics</td>
                <td style="padding: 0.75rem;"><span style="color: #38a169;">✓ Automated</span></td>
            </tr>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 0.75rem;">Revaluation</td>
                <td style="padding: 0.75rem;">Monthly</td>
                <td style="padding: 0.75rem;">Last day of month</td>
                <td style="padding: 0.75rem;">Accounting</td>
                <td style="padding: 0.75rem;"><span style="color: #38a169;">✓ Automated</span></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- BNM Reference -->
<div class="card">
    <h2>BNM Compliance Reference</h2>
    <div class="alert alert-info">
        <strong>AML/CFT Policy (Revised 2025):</strong> Money Services Businesses must submit LCTR for cash transactions ≥ RM 25,000.<br>
        <strong>Record Keeping:</strong> All transaction records must be retained for minimum 7 years from transaction date.<br>
        <strong>PDPA 2010 (Amended 2024):</strong> Personal data must be encrypted and retained only as long as necessary.
    </div>
</div>
@endsection
