@extends('layouts.app')

@section('title', 'Generate Report')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Generate Report</h1>

    <div class="bg-white rounded-lg shadow p-6 max-w-lg">
        <form action="{{ route('compliance.reporting.run') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="report_type" class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                <select name="report_type" id="report_type" class="w-full border rounded px-3 py-2" required>
                    <option value="">Select Report Type</option>
                    @foreach($reportTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4" id="date-field" style="display: none;">
                <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input type="date" name="date" id="date" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4" id="month-field" style="display: none;">
                <label for="month" class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                <input type="month" name="month" id="month" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4" id="quarter-field" style="display: none;">
                <label for="quarter" class="block text-sm font-medium text-gray-700 mb-2">Quarter (e.g., 2026-Q1)</label>
                <input type="text" name="quarter" id="quarter" placeholder="2026-Q1" class="w-full border rounded px-3 py-2">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Generate Report
                </button>
                <a href="{{ route('compliance.reporting.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('report_type').addEventListener('change', function() {
    const type = this.value;
    document.getElementById('date-field').style.display = type === 'msb2' ? 'block' : 'none';
    document.getElementById('month-field').style.display = ['lctr', 'lmca'].includes(type) ? 'block' : 'none';
    document.getElementById('quarter-field').style.display = type === 'qlvr' ? 'block' : 'none';
});
</script>
@endpush
@endsection