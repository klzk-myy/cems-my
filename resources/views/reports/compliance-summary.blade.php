@extends('layouts.app')

@section('title', 'Compliance Summary - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-1">Compliance Summary</h2>
    <p class="text-gray-500 text-sm">AML/CFT monitoring and regulatory compliance overview</p>
</div>

<!-- Date Range Filter -->
<form method="GET" action="{{ route('reports.compliance-summary') }}" class="bg-white rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div class="flex-1 min-w-40">
        <label for="start_date" class="block mb-1 text-sm font-medium text-gray-600">Start Date</label>
        <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="w-full p-2 border border-gray-200 rounded text-sm">
    </div>
    <div class="flex-1 min-w-40">
        <label for="end_date" class="block mb-1 text-sm font-medium text-gray-600">End Date</label>
        <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="w-full p-2 border border-gray-200 rounded text-sm">
    </div>
    <div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-semibold text-sm hover:bg-blue-700 transition-colors">Update Report</button>
    </div>
</form>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white border-l-4 border-blue-500 rounded-lg p-6">
        <div class="text-3xl font-bold text-gray-800">{{ $flaggedStats->sum('count') }}</div>
        <div class="text-sm text-gray-500 mt-2">Total Flagged Transactions</div>
    </div>

    <div class="bg-white border-l-4 border-yellow-500 rounded-lg p-6">
        <div class="text-3xl font-bold text-yellow-600">{{ $largeTransactions }}</div>
        <div class="text-sm text-gray-500 mt-2">Large Transactions (≥RM 50k)</div>
    </div>

    <div class="bg-white border-l-4 border-yellow-500 rounded-lg p-6">
        <div class="text-3xl font-bold text-yellow-600">{{ $eddCount }}</div>
        <div class="text-sm text-gray-500 mt-2">EDD Required Transactions</div>
    </div>

    <div class="bg-white border-l-4 border-red-600 rounded-lg p-6">
        <div class="text-3xl font-bold text-red-600">{{ $suspiciousCount }}</div>
        <div class="text-sm text-gray-500 mt-2">Suspicious Activities</div>
    </div>
</div>

<!-- Flag Type Breakdown -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Flag Type Breakdown</h3>

    <table class="w-full border-collapse">
        <thead>
            <tr>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Flag Type</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Count</th>
                <th class="text-right px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Percentage</th>
                <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($flaggedStats as $stat)
            @php
                $total = $flaggedStats->sum('count');
                $percentage = $total > 0 ? ($stat->count / $total * 100) : 0;
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 border-b border-gray-100">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $stat->flag_type === 'Velocity' ? 'bg-blue-100 text-blue-800' : ($stat->flag_type === 'Structuring' ? 'bg-orange-100 text-orange-800' : ($stat->flag_type === 'Sanction_Match' ? 'bg-red-100 text-red-800' : ($stat->flag_type === 'EDD_Required' ? 'bg-purple-100 text-purple-800' : ($stat->flag_type === 'PEP_Status' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'))) }}">
                        {{ $stat->flag_type }}
                    </span>
                </td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">{{ number_format($stat->count) }}</td>
                <td class="px-4 py-3 border-b border-gray-100 text-right">{{ number_format($percentage, 1) }}%</td>
                <td class="px-4 py-3 border-b border-gray-100">
                    @if($stat->flag_type === 'Sanction_Match' || $stat->flag_type === 'Structuring')
                        <span class="text-red-600 font-semibold">High Priority</span>
                    @elseif($stat->flag_type === 'EDD_Required')
                        <span class="text-orange-500 font-semibold">Medium Priority</span>
                    @else
                        <span class="text-green-600">Standard</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                    No flagged transactions in this period.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- BNM Reporting Checklist -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">BNM Compliance Checklist</h3>

    <div class="divide-y divide-gray-200">
        <div class="flex items-center py-4">
            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-4 {{ $largeTransactions > 0 ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                {{ $largeTransactions > 0 ? '✓' : '○' }}
            </div>
            <div>
                <strong>LCTR Report</strong>
                <p class="text-gray-500 text-sm mt-1">
                    Large Currency Transaction Report
                    @if($largeTransactions > 0)
                        <br><span class="text-red-600">{{ $largeTransactions }} qualifying transactions require reporting</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center py-4">
            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-4 {{ $suspiciousCount > 0 ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800' }}">
                {{ $suspiciousCount > 0 ? '!' : '✓' }}
            </div>
            <div>
                <strong>Suspicious Activity Report (SAR)</strong>
                <p class="text-gray-500 text-sm mt-1">
                    @if($suspiciousCount > 0)
                        <span class="text-red-600">{{ $suspiciousCount }} suspicious activities pending review</span>
                    @else
                        No suspicious activities detected
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center py-4">
            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-4 bg-green-100 text-green-800">✓</div>
            <div>
                <strong>CDD/EDD Documentation</strong>
                <p class="text-gray-500 text-sm mt-1">
                    @if($eddCount > 0)
                        {{ $eddCount }} transactions require enhanced due diligence
                    @else
                        All CDD requirements met
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center py-4">
            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-4 bg-green-100 text-green-800">✓</div>
            <div>
                <strong>MSB(2) Daily Report</strong>
                <p class="text-gray-500 text-sm mt-1">
                    Daily transaction summary report - Due next business day
                </p>
            </div>
        </div>

        <div class="flex items-center py-4">
            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-4 bg-green-100 text-green-800">✓</div>
            <div>
                <strong>Sanctions Screening</strong>
                <p class="text-gray-500 text-sm mt-1">
                    Real-time sanctions screening active
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Action Items -->
<div class="bg-white rounded-lg shadow-sm p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Required Actions</h3>

    @if($flaggedStats->sum('count') > 0 || $largeTransactions > 0)
    <ul class="list-none p-0">
        @if($largeTransactions > 0)
        <li class="py-4 border-b border-gray-200">
            <span class="text-red-600 font-semibold">⚠ URGENT:</span>
            Generate LCTR report for {{ $largeTransactions }} large transactions
            <a href="{{ route('reports.lctr') }}" class="ml-4 px-3 py-1 bg-blue-600 text-white no-underline rounded text-sm hover:bg-blue-700 transition-colors">Generate Report</a>
        </li>
        @endif

        @if($suspiciousCount > 0)
        <li class="py-4 border-b border-gray-200">
            <span class="text-red-600 font-semibold">⚠ URGENT:</span>
            Review {{ $suspiciousCount }} suspicious activities in Compliance Portal
            <a href="{{ route('compliance') }}" class="ml-4 px-3 py-1 bg-yellow-500 text-white no-underline rounded text-sm hover:bg-yellow-600 transition-colors">Review Flags</a>
        </li>
        @endif

        @if($eddCount > 0)
        <li class="py-4">
            <span class="text-orange-500 font-semibold">⚠ ACTION REQUIRED:</span>
            Complete EDD for {{ $eddCount }} high-value transactions
        </li>
        @endif
    </ul>
    @else
    <p class="text-green-600 p-4 bg-green-50 rounded">✅ All compliance requirements are up to date. No action required.</p>
    @endif
</div>
@endsection
