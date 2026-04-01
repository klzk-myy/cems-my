@extends('layouts.app')

@section('title', 'LCTR Report - CEMS-MY')

@section('content')
<div class="lctr-header">
    <h2>Large Cash Transaction Report (LCTR)</h2>
    <p>Monthly submission for BNM AML/CFT compliance</p>
</div>

<!-- Report Configuration -->
<div class="card">
    <h2>Report Configuration</h2>
    <div class="config-info">
        <div class="info-row">
            <span class="label">Report Type:</span>
            <span class="value">Large Cash Transaction Report (LCTR)</span>
        </div>
        <div class="info-row">
            <span class="label">Threshold:</span>
            <span class="value">≥ RM 25,000</span>
        </div>
        <div class="info-row">
            <span class="label">Frequency:</span>
            <span class="value">Monthly</span>
        </div>
        <div class="info-row">
            <span class="label">Submission Deadline:</span>
            <span class="value">5th of following month</span>
        </div>
    </div>
    
    <form id="lctr-form" class="date-form">
        <div class="form-row">
            <div class="form-group">
                <label for="month">Select Month:</label>
                <input type="month" id="month" name="month" value="{{ $month }}" class="form-control">
            </div>
            <div class="form-group">
                <button type="button" id="generate-btn" class="btn btn-primary">Generate Report</button>
                <button type="button" id="export-btn" class="btn btn-success" disabled>Export CSV</button>
            </div>
        </div>
    </form>
</div>

<!-- Report Preview -->
<div class="card" id="report-preview" style="display: none;">
    <h2>Report Preview</h2>
    <div id="report-summary" class="report-summary"></div>
    <div id="report-table-container" style="overflow-x: auto;">
        <table id="report-table" style="min-width: 1200px;">
            <thead id="report-head"></thead>
            <tbody id="report-body"></tbody>
        </table>
    </div>
</div>

<!-- Report Fields Reference -->
<div class="card">
    <h2>Report Fields</h2>
    <div class="fields-grid">
        <div class="field-item">
            <span class="field-name">Transaction_ID</span>
            <span class="field-desc">Unique identifier for transaction</span>
        </div>
        <div class="field-item">
            <span class="field-name">Transaction_Date</span>
            <span class="field-desc">Date of transaction (YYYY-MM-DD)</span>
        </div>
        <div class="field-item">
            <span class="field-name">Transaction_Time</span>
            <span class="field-desc">Time of transaction (HH:MM:SS)</span>
        </div>
        <div class="field-item">
            <span class="field-name">Customer_ID_Type</span>
            <span class="field-desc">MyKad, Passport, etc.</span>
        </div>
        <div class="field-item">
            <span class="field-name">Customer_ID_Number</span>
            <span class="field-desc">Customer identification number</span>
        </div>
        <div class="field-item">
            <span class="field-name">Customer_Name</span>
            <span class="field-desc">Full customer name</span>
        </div>
        <div class="field-item">
            <span class="field-name">Amount_Local</span>
            <span class="field-desc">Transaction amount in MYR</span>
        </div>
        <div class="field-item">
            <span class="field-name">Amount_Foreign</span>
            <span class="field-desc">Transaction amount in foreign currency</span>
        </div>
        <div class="field-item">
            <span class="field-name">Currency_Code</span>
            <span class="field-desc">Foreign currency code (USD, EUR, etc.)</span>
        </div>
        <div class="field-item">
            <span class="field-name">Exchange_Rate</span>
            <span class="field-desc">Rate applied to transaction</span>
        </div>
        <div class="field-item">
            <span class="field-name">Till_ID</span>
            <span class="field-desc">Branch/till identifier</span>
        </div>
        <div class="field-item">
            <span class="field-name">Teller_ID</span>
            <span class="field-desc">User who processed transaction</span>
        </div>
        <div class="field-item">
            <span class="field-name">Purpose</span>
            <span class="field-desc">Transaction purpose</span>
        </div>
        <div class="field-item">
            <span class="field-name">Source_of_Funds</span>
            <span class="field-desc">Source of customer funds</span>
        </div>
        <div class="field-item">
            <span class="field-name">CDD_Level</span>
            <span class="field-desc">Due diligence level applied</span>
        </div>
        <div class="field-item">
            <span class="field-name">Status</span>
            <span class="field-desc">Transaction status</span>
        </div>
    </div>
</div>

@section('styles')
<style>
    .lctr-header {
        margin-bottom: 1.5rem;
    }
    .lctr-header h2 {
        margin-bottom: 0.5rem;
        color: #2d3748;
    }
    .lctr-header p {
        color: #718096;
    }
    .config-info {
        background: #f7fafc;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .info-row {
        display: flex;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .label {
        width: 180px;
        font-weight: 500;
        color: #4a5568;
    }
    .value {
        color: #2d3748;
    }
    .date-form {
        margin-top: 1.5rem;
    }
    .form-row {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
    }
    .form-group {
        flex: 1;
    }
    .form-group:last-child {
        flex: 0 0 auto;
        display: flex;
        gap: 0.5rem;
    }
    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
    }
    .report-summary {
        background: #f7fafc;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 2rem;
    }
    .summary-item {
        display: flex;
        flex-direction: column;
    }
    .summary-label {
        color: #718096;
        font-size: 0.875rem;
    }
    .summary-value {
        font-weight: 600;
        font-size: 1.25rem;
        color: #2d3748;
    }
    .fields-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 0.75rem;
    }
    .field-item {
        display: flex;
        flex-direction: column;
        padding: 0.75rem;
        background: #f7fafc;
        border-radius: 4px;
    }
    .field-name {
        font-weight: 600;
        color: #2d3748;
        font-family: monospace;
        font-size: 0.875rem;
    }
    .field-desc {
        color: #718096;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }
    table {
        width: 100%;
    }
    th, td {
        padding: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }
    th {
        background: #f7fafc;
        font-weight: 600;
        color: #2d3748;
    }
</style>
@endsection

@section('scripts')
<script>
    document.getElementById('generate-btn').addEventListener('click', async function() {
        const month = document.getElementById('month').value;
        
        try {
            const response = await fetch(`/reports/lctr/generate?month=${month}`);
            const data = await response.json();
            
            // Show report preview
            document.getElementById('report-preview').style.display = 'block';
            
            // Update summary
            const summaryDiv = document.getElementById('report-summary');
            summaryDiv.innerHTML = `
                <div class="summary-item">
                    <span class="summary-label">Month</span>
                    <span class="summary-value">${data.month}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Transactions</span>
                    <span class="summary-value">${data.total_transactions}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Amount</span>
                    <span class="summary-value">RM ${parseFloat(data.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Generated</span>
                    <span class="summary-value">${new Date(data.generated_at).toLocaleString()}</span>
                </div>
            `;
            
            // Build table
            if (data.data.length > 0) {
                const headers = Object.keys(data.data[0]);
                const thead = document.getElementById('report-head');
                const tbody = document.getElementById('report-body');
                
                thead.innerHTML = '<tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr>';
                tbody.innerHTML = data.data.map(row => 
                    '<tr>' + headers.map(h => `<td>${row[h] || '-'}</td>`).join('') + '</tr>'
                ).join('');
            }
            
            // Enable export button
            document.getElementById('export-btn').disabled = false;
            
        } catch (error) {
            alert('Failed to generate report: ' + error.message);
        }
    });
</script>
@endsection
@endsection
