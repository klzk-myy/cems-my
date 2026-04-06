@extends('layouts.app')

@section('title', 'Batch Transaction Upload - CEMS-MY')

@section('styles')
<style>
    .batch-header {
        margin-bottom: 1.5rem;
    }
    .batch-header h2 {
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .batch-header p {
        color: #718096;
    }

    .upload-form {
        background: #fff;
        border: 2px dashed #e2e8f0;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .upload-form:hover {
        border-color: #3182ce;
    }

    .upload-zone {
        padding: 2rem;
        cursor: pointer;
    }

    .upload-zone i {
        font-size: 3rem;
        color: #cbd5e0;
        margin-bottom: 1rem;
    }

    .upload-zone p {
        color: #718096;
        margin-bottom: 0.5rem;
    }

    .upload-zone .btn {
        margin-top: 1rem;
    }

    .file-info {
        margin-top: 1rem;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 4px;
    }

    .instructions-panel {
        background: #f7fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .instructions-panel h3 {
        color: #2d3748;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .instructions-panel ul {
        margin-left: 1.5rem;
        color: #4a5568;
    }

    .instructions-panel li {
        margin-bottom: 0.5rem;
    }

    .instructions-panel code {
        background: #edf2f7;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        font-size: 0.9rem;
    }

    .csv-columns {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 1rem;
        margin: 1rem 0;
    }

    .csv-columns table {
        width: 100%;
        border-collapse: collapse;
    }

    .csv-columns th,
    .csv-columns td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .csv-columns th {
        background: #edf2f7;
        font-weight: 600;
        color: #2d3748;
    }

    .imports-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .imports-table th,
    .imports-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .imports-table th {
        background: #edf2f7;
        font-weight: 600;
        color: #2d3748;
    }

    .imports-table tr:hover {
        background: #f7fafc;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-success {
        background: #c6f6d5;
        color: #276749;
    }

    .badge-warning {
        background: #feebc8;
        color: #c05621;
    }

    .badge-danger {
        background: #fed7d7;
        color: #c53030;
    }

    .badge-secondary {
        background: #e2e8f0;
        color: #4a5568;
    }

    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .status-dot.pending { background: #ed8936; }
    .status-dot.processing { background: #3182ce; }
    .status-dot.completed { background: #38a169; }
    .status-dot.failed { background: #e53e3e; }

    .count-box {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
    }

    .count-success { background: #c6f6d5; color: #276749; }
    .count-error { background: #fed7d7; color: #c53030; }
</style>
@endsection

@section('content')
<div class="batch-header">
    <h2>Batch Transaction Upload</h2>
    <p>Import multiple transactions from a CSV file</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<!-- Instructions Panel -->
<div class="instructions-panel">
    <h3>CSV Format Requirements</h3>
    <p>Your CSV file must include a header row with the following columns:</p>
    <div class="csv-columns">
        <table>
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Description</th>
                    <th>Example</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>customer_id</code></td>
                    <td>Customer ID (must exist in system)</td>
                    <td>1</td>
                </tr>
                <tr>
                    <td><code>type</code></td>
                    <td>Transaction type: Buy or Sell</td>
                    <td>Buy</td>
                </tr>
                <tr>
                    <td><code>currency_code</code></td>
                    <td>Currency code (e.g., USD, EUR)</td>
                    <td>USD</td>
                </tr>
                <tr>
                    <td><code>amount_foreign</code></td>
                    <td>Amount in foreign currency</td>
                    <td>1000.00</td>
                </tr>
                <tr>
                    <td><code>rate</code></td>
                    <td>Exchange rate</td>
                    <td>4.7200</td>
                </tr>
                <tr>
                    <td><code>purpose</code></td>
                    <td>Purpose of transaction</td>
                    <td>Business Travel</td>
                </tr>
                <tr>
                    <td><code>source_of_funds</code></td>
                    <td>Source of funds</td>
                    <td>Salary</td>
                </tr>
                <tr>
                    <td><code>till_id</code></td>
                    <td>Till identifier (optional, defaults to MAIN)</td>
                    <td>MAIN</td>
                </tr>
            </tbody>
        </table>
    </div>
    <ul>
        <li>File must be in <strong>UTF-8 encoding</strong></li>
        <li>Maximum file size: <strong>2MB</strong></li>
        <li>Supported formats: <strong>.csv, .txt</strong></li>
        <li>The till must be <strong>open</strong> for the currency being traded</li>
        <li>For Sell transactions, ensure <strong>sufficient stock</strong> is available</li>
    </ul>
    <a href="{{ route('transactions.template') }}" class="btn btn-secondary">
        <i class="icon-download"></i> Download Template
    </a>
</div>

<!-- Upload Form -->
<div class="upload-form" id="upload-form">
    <form action="{{ route('transactions.batch-upload') }}" method="POST" enctype="multipart/form-data" id="csv-upload-form">
        @csrf
        <div class="upload-zone" onclick="document.getElementById('csv_file').click()">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📁</div>
            <p><strong>Click to select a CSV file</strong></p>
            <p>or drag and drop here</p>
            <p style="font-size: 0.875rem; color: #a0aec0;">CSV, TXT up to 2MB</p>
            <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required style="display: none;">
            <button type="submit" class="btn btn-success" style="margin-top: 1rem;">
                Upload & Process
            </button>
        </div>
        <div id="file-info" class="file-info" style="display: none;">
            <strong>Selected:</strong> <span id="filename"></span>
            <span id="filesize" style="color: #718096; margin-left: 0.5rem;"></span>
        </div>
        @error('csv_file')
            <div class="alert alert-danger" style="margin-top: 1rem;">{{ $message }}</div>
        @enderror
    </form>
</div>

<!-- Recent Imports -->
@if($recentImports->count() > 0)
<div class="card">
    <h3>Recent Imports</h3>
    <table class="imports-table">
        <thead>
            <tr>
                <th>Filename</th>
                <th>Date</th>
                <th>Total Rows</th>
                <th>Success</th>
                <th>Errors</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentImports as $import)
            <tr>
                <td>{{ $import->original_filename }}</td>
                <td>{{ $import->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $import->total_rows }}</td>
                <td>
                    <span class="count-box count-success">
                        ✓ {{ $import->success_count }}
                    </span>
                </td>
                <td>
                    @if($import->error_count > 0)
                        <span class="count-box count-error">
                            ✗ {{ $import->error_count }}
                        </span>
                    @else
                        -
                    @endif
                </td>
                <td>
                    <span class="badge badge-{{ $import->getStatusColor() }}">
                        <span class="status-indicator">
                            <span class="status-dot {{ $import->status }}"></span>
                            {{ ucfirst($import->status) }}
                        </span>
                    </span>
                </td>
                <td>
                    <a href="{{ route('transactions.batch-upload.show', $import) }}" class="btn btn-sm">
                        View Results
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection

@section('scripts')
<script>
    const fileInput = document.getElementById('csv_file');
    const fileInfo = document.getElementById('file-info');
    const filename = document.getElementById('filename');
    const filesize = document.getElementById('filesize');

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            filename.textContent = file.name;
            filesize.textContent = '(' + (file.size / 1024).toFixed(1) + ' KB)';
            fileInfo.style.display = 'block';
        }
    });

    // Drag and drop support
    const uploadForm = document.getElementById('upload-form');

    uploadForm.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadForm.style.borderColor = '#3182ce';
        uploadForm.style.background = '#ebf8ff';
    });

    uploadForm.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadForm.style.borderColor = '#e2e8f0';
        uploadForm.style.background = '#fff';
    });

    uploadForm.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadForm.style.borderColor = '#e2e8f0';
        uploadForm.style.background = '#fff';

        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].name.endsWith('.csv') || files[0].name.endsWith('.txt')) {
            fileInput.files = files;
            filename.textContent = files[0].name;
            filesize.textContent = '(' + (files[0].size / 1024).toFixed(1) + ' KB)';
            fileInfo.style.display = 'block';
        }
    });
</script>
@endsection
