@extends('layouts.app')

@section('title', 'Batch Transaction Upload - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Batch Transaction Upload</h2>
    <p class="text-gray-500 text-sm">Import multiple transactions from a CSV file</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<!-- Instructions Panel -->
<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">CSV Format Requirements</h3>
    <p class="text-gray-600 mb-4">Your CSV file must include a header row with the following columns:</p>
    <div class="bg-white border border-gray-200 rounded p-4 my-4">
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Column</th>
                    <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Description</th>
                    <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Example</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-3 py-2 border-b border-gray-200"><code class="bg-gray-100 px-2 py-1 rounded text-sm">customer_id</code></td>
                    <td class="px-3 py-2 border-b border-gray-200">Customer ID (must exist in system)</td>
                    <td class="px-3 py-2 border-b border-gray-200">1</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 border-b border-gray-200"><code class="bg-gray-100 px-2 py-1 rounded text-sm">type</code></td>
                    <td class="px-3 py-2 border-b border-gray-200">Transaction type: Buy or Sell</td>
                    <td class="px-3 py-2 border-b border-gray-200">Buy</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 border-b border-gray-200"><code class="bg-gray-100 px-2 py-1 rounded text-sm">currency_code</code></td>
                    <td class="px-3 py-2 border-b border-gray-200">Currency code (e.g., USD, EUR)</td>
                    <td class="px-3 py-2 border-b border-gray-200">USD</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 border-b border-gray-200"><code class="bg-gray-100 px-2 py-1 rounded text-sm">amount_foreign</code></td>
                    <td class="px-3 py-2 border-b border-gray-200">Amount in foreign currency</td>
                    <td class="px-3 py-2 border-b border-gray-200">1000.00</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 border-b border-gray-200"><code class="bg-gray-100 px-2 py-1 rounded text-sm">rate</code></td>
                    <td class="px-3 py-2 border-b border-gray-200">Exchange rate</td>
                    <td class="px-3 py-2 border-b border-gray-200">4.7200</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 border-b border-gray-200"><code class="bg-gray-100 px-2 py-1 rounded text-sm">purpose</code></td>
                    <td class="px-3 py-2 border-b border-gray-200">Purpose of transaction</td>
                    <td class="px-3 py-2 border-b border-gray-200">Business Travel</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 border-b border-gray-200"><code class="bg-gray-100 px-2 py-1 rounded text-sm">source_of_funds</code></td>
                    <td class="px-3 py-2 border-b border-gray-200">Source of funds</td>
                    <td class="px-3 py-2 border-b border-gray-200">Salary</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 border-b border-gray-200"><code class="bg-gray-100 px-2 py-1 rounded text-sm">till_id</code></td>
                    <td class="px-3 py-2 border-b border-gray-200">Till identifier (optional, defaults to MAIN)</td>
                    <td class="px-3 py-2 border-b border-gray-200">MAIN</td>
                </tr>
            </tbody>
        </table>
    </div>
    <ul class="list-disc ml-6 text-gray-600 space-y-1">
        <li>File must be in <strong>UTF-8 encoding</strong></li>
        <li>Maximum file size: <strong>2MB</strong></li>
        <li>Supported formats: <strong>.csv, .txt</strong></li>
        <li>The till must be <strong>open</strong> for the currency being traded</li>
        <li>For Sell transactions, ensure <strong>sufficient stock</strong> is available</li>
    </ul>
    <a href="{{ route('transactions.template') }}" class="btn btn-secondary mt-4">
        <i class="icon-download"></i> Download Template
    </a>
</div>

<!-- Upload Form -->
<div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-8 text-center mb-8 hover:border-blue-500 transition-colors" id="upload-form">
    <form action="{{ route('transactions.batch-upload') }}" method="POST" enctype="multipart/form-data" id="csv-upload-form">
        @csrf
        <div class="cursor-pointer" onclick="document.getElementById('csv_file').click()">
            <div class="text-5xl mb-4">📁</div>
            <p><strong>Click to select a CSV file</strong></p>
            <p class="text-gray-500 mb-1">or drag and drop here</p>
            <p class="text-sm text-gray-400">CSV, TXT up to 2MB</p>
            <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required class="hidden">
            <button type="submit" class="btn btn-success mt-4">
                Upload & Process
            </button>
        </div>
        <div id="file-info" class="mt-4 p-4 bg-gray-50 rounded hidden">
            <strong>Selected:</strong> <span id="filename"></span>
            <span id="filesize" class="text-gray-500 ml-2"></span>
        </div>
        @error('csv_file')
            <div class="alert alert-danger mt-4">{{ $message }}</div>
        @enderror
    </form>
</div>

<!-- Recent Imports -->
@if($recentImports->count() > 0)
<div class="card">
    <h3>Recent Imports</h3>
    <table class="w-full border-collapse mt-4">
        <thead>
            <tr>
                <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Filename</th>
                <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Date</th>
                <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Total Rows</th>
                <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Success</th>
                <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Errors</th>
                <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Status</th>
                <th class="bg-gray-100 text-gray-700 font-semibold px-3 py-2 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentImports as $import)
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 border-b border-gray-200">{{ $import->original_filename }}</td>
                <td class="px-3 py-2 border-b border-gray-200">{{ $import->created_at->format('Y-m-d H:i') }}</td>
                <td class="px-3 py-2 border-b border-gray-200">{{ $import->total_rows }}</td>
                <td class="px-3 py-2 border-b border-gray-200">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-sm bg-green-100 text-green-800">
                        ✓ {{ $import->success_count }}
                    </span>
                </td>
                <td class="px-3 py-2 border-b border-gray-200">
                    @if($import->error_count > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-sm bg-red-100 text-red-800">
                            ✗ {{ $import->error_count }}
                        </span>
                    @else
                        -
                    @endif
                </td>
                <td class="px-3 py-2 border-b border-gray-200">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-{{ $import->getStatusColor() === 'success' ? 'green-100 text-green-800' : ($import->getStatusColor() === 'warning' ? 'orange-100 text-orange-800' : 'gray-100 text-gray-800') }}">
                        <span class="w-2 h-2 rounded-full mr-1 @if($import->status === 'pending') bg-orange-500 @elseif($import->status === 'processing') bg-blue-500 @elseif($import->status === 'completed') bg-green-500 @else bg-red-500 @endif"></span>
                        {{ ucfirst($import->status) }}
                    </span>
                </td>
                <td class="px-3 py-2 border-b border-gray-200">
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
        uploadForm.classList.add('border-blue-500', 'bg-blue-50');
        uploadForm.classList.remove('border-gray-300');
    });

    uploadForm.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadForm.classList.remove('border-blue-500', 'bg-blue-50');
        uploadForm.classList.add('border-gray-300');
    });

    uploadForm.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadForm.classList.remove('border-blue-500', 'bg-blue-50');
        uploadForm.classList.add('border-gray-300');

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
