@extends('layouts.app')

@section('title', 'MSB(2) Report - CEMS-MY')

@section('content')
<div class="msb2-header">
    <h2>MSB(2) Daily Statistical Report</h2>
    <p>Daily aggregated transaction data for BNM statistical reporting</p>
</div>

<!-- Report Configuration -->
<div class="card">
    <h2>Report Configuration</h2>
    <div class="config-info">
        <div class="info-row">
            <span class="label">Report Type:</span>
            <span class="value">MSB(2) Daily Statistical Report</span>
        </div>
        <div class="info-row">
            <span class="label">Frequency:</span>
            <span class="value">Daily</span>
        </div>
        <div class="info-row">
            <span class="label">Submission Deadline:</span>
            <span class="value">Next business day by 9:00 AM</span>
        </div>
        <div class="info-row">
            <span class="label">Purpose:</span>
            <span class="value">Statistical monitoring (no PII)</span>
        </div>
    </div>
    
    <form id="msb2-form" class="date-form">
        <div class="form-row">
            <div class="form-group">
                <label for="date">Select Date:</label>
                <input type="date" id="date" name="date" value="{{ $date }}" class="form-control">
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
        <table id="report-table">
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
            <span class="field-name">Date</span>
            <span class="field-desc">Report date (YYYY-MM-DD)</span>
        </div>
        <div class="field-item">
            <span class="field-name">Currency</span>
            <span class="field-desc">Foreign currency code</span>
        </div>
        <div class="field-item">
            <span class="field-name">Buy_Volume_MYR</span>
            <span class="field-desc">Total MYR value of buy transactions</span>
        </div>
        <div class="field-item">
            <span class="field-name">Buy_Count</span>
            <span class="field-desc">Number of buy transactions</span>
        </div>
        <div class="field-item">
            <span class="field-name">Sell_Volume_MYR</span>
            <span class="field-desc">Total MYR value of sell transactions</span>
        </div>
        <div class="field-item">
            <span class="field-name">Sell_Count</span>
            <span class="field-desc">Number of sell transactions</span>
        </div>
        <div class="field-item">
            <span class="field-name">Avg_Buy_Rate</span>
            <span class="field-desc">Average exchange rate for buys</span>
        </div>
        <div class="field-item">
            <span class="field-name">Avg_Sell_Rate</span>
            <span class="field-desc">Average exchange rate for sells</span>
        </div>
        <div class="field-item">
            <span class="field-name">Opening_Position</span>
            <span class="field-desc">Opening foreign currency balance</span>
        </div>
        <div class="field-item">
            <span class="field-name">Closing_Position</span>
            <span class="field-desc">Closing foreign currency balance</span>
        </div>
    </div>
</div>

<!-- Schedule Information -->
<div class="card">
    <h2>Automation Schedule</h2>
    <div class="alert alert-info">
        <strong>Automatic Generation:</strong> Runs daily at 00:05 for previous day's transactions<br>
        <strong>Next Run:</strong> {{ now()->addDay()->format('Y-m-d 00:05') }}<br>
        <strong>Storage:</strong> Reports saved to <code>/storage/app/reports/</code><br>
        <strong>Retention:</strong> 90 days
    </div>
</div>

@section('styles')
<style>
    .msb2-header {
        margin-bottom: 1.5rem;
    }
    .msb2-header h2 {
        margin-bottom: 0.5rem;
        color: #2d3748;
    }
    .msb2-header p {
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
        flex-wrap: wrap;
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
    code {
        background: #edf2f7;
        padding: 0.125rem 0.375rem;
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.875rem;
    }
</style>
@endsection

@section('scripts')
<script>
    document.getElementById('generate-btn').addEventListener('click', async function() {
        const date = document.getElementById('date').value;
        
        try {
            const response = await fetch(`/reports/msb2/generate?date=${date}`);
            const data = await response.json();
            
            // Show report preview
            document.getElementById('report-preview').style.display = 'block';
            
            // Update summary
            const summaryDiv = document.getElementById('report-summary');
            summaryDiv.innerHTML = `
                <div class="summary-item">
                    <span class="summary-label">Date</span>
                    <span class="summary-value">${data.date}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Currencies</span>
                    <span class="summary-value">${data.data.length}</span>
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
            } else {
                document.getElementById('report-body').innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 2rem;">No data available for selected date</td></tr>';
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
