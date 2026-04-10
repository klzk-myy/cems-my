@extends('layouts.app')

@section('title', 'Compliance Portal - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-2">Compliance Portal</h2>
    <p class="text-gray-500 text-sm">Review and resolve suspicious transaction flags for AML monitoring</p>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="text-4xl font-bold text-red-600">{{ $stats['open'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-2">Open Flags</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="text-4xl font-bold text-orange-500">{{ $stats['under_review'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-2">Under Review</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="text-4xl font-bold text-green-600">{{ $stats['resolved_today'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-2">Resolved Today</div>
    </div>
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="text-4xl font-bold text-red-600">{{ $stats['high_priority'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-2">High Priority</div>
    </div>
</div>

<!-- STR Deadline Warning -->
@if(isset($strStats) && ($strStats['overdue'] > 0 || $strStats['near_deadline'] > 0))
<div class="mt-6 p-4 rounded-lg bg-yellow-100 border border-yellow-400">
    <h4 class="mb-2 text-yellow-800 font-semibold">STR Filing Deadline Warning</h4>
    @if($strStats['overdue'] > 0)
    <p class="text-red-700">
        <strong>{{ $strStats['overdue'] }} STR(s) overdue</strong> - Filing deadline (3 working days from suspicion) has passed. Immediate action required.
    </p>
    @endif
    @if($strStats['near_deadline'] > 0)
    <p class="mt-2 text-yellow-700">
        <strong>{{ $strStats['near_deadline'] }} STR(s) approaching deadline</strong> - Filing deadline within 2 days.
    </p>
    @endif
</div>
@endif

<!-- Filter Bar -->
<div class="bg-gray-50 rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-center">
    <label for="status-filter" class="font-semibold text-gray-600 text-sm">Status:</label>
    <select id="status-filter" onchange="window.location.href='?status='+this.value+'&flag_type={{ request('flag_type', 'all') }}'" class="p-2 border border-gray-200 rounded text-sm bg-white cursor-pointer">
        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
        <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
        <option value="Under_Review" {{ request('status') == 'Under_Review' ? 'selected' : '' }}>Under Review</option>
        <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
    </select>

    <label for="type-filter" class="font-semibold text-gray-600 text-sm">Flag Type:</label>
    <select id="type-filter" onchange="window.location.href='?status={{ request('status', 'all') }}&flag_type='+this.value" class="p-2 border border-gray-200 rounded text-sm bg-white cursor-pointer">
        <option value="all" {{ request('flag_type') == 'all' ? 'selected' : '' }}>All Types</option>
        <option value="Velocity" {{ request('flag_type') == 'Velocity' ? 'selected' : '' }}>Velocity</option>
        <option value="Structuring" {{ request('flag_type') == 'Structuring' ? 'selected' : '' }}>Structuring</option>
        <option value="Large_Amount" {{ request('flag_type') == 'Large_Amount' ? 'selected' : '' }}>Large Amount</option>
        <option value="EDD_Required" {{ request('flag_type') == 'EDD_Required' ? 'selected' : '' }}>EDD Required</option>
        <option value="Sanction_Match" {{ request('flag_type') == 'Sanction_Match' ? 'selected' : '' }}>Sanction Match</option>
        <option value="Pep_Status" {{ request('flag_type') == 'Pep_Status' ? 'selected' : '' }}>PEP Status</option>
        <option value="High_Risk_Customer" {{ request('flag_type') == 'High_Risk_Customer' ? 'selected' : '' }}>High Risk Customer</option>
        <option value="High_Risk_Country" {{ request('flag_type') == 'High_Risk_Country' ? 'selected' : '' }}>High Risk Country</option>
        <option value="Round_Amount" {{ request('flag_type') == 'Round_Amount' ? 'selected' : '' }}>Round Amount</option>
        <option value="Profile_Deviation" {{ request('flag_type') == 'Profile_Deviation' ? 'selected' : '' }}>Profile Deviation</option>
        <option value="Manual_Review" {{ request('flag_type') == 'Manual_Review' ? 'selected' : '' }}>Manual Review</option>
    </select>

    <a href="{{ route('compliance') }}" class="ml-auto px-4 py-2 bg-blue-600 text-white no-underline rounded text-sm font-semibold hover:bg-blue-700 transition-colors">Clear Filters</a>
</div>

<!-- Flags Table -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Flagged Transactions</h2>

    @if($flags->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Flag ID</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Transaction</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Customer</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Type</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Reason</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Status</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Assigned To</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Created</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($flags as $flag)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100">#{{ $flag->id }}</td>
                    <td class="px-4 py-3 border-b border-gray-100">
                        @if($flag->transaction)
                        <a href="{{ route('transactions.show', $flag->transaction) }}" class="text-blue-600 no-underline font-semibold hover:underline">
                            #{{ $flag->transaction->id }}
                        </a>
                        @else
                        <span class="text-gray-500">N/A</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100">{{ $flag->transaction?->customer?->full_name ?? 'N/A' }}</td>
                    <td class="px-4 py-3 border-b border-gray-100">
                        @php
                        $typeClass = match($flag->flag_type->value) {
                            'Velocity' => 'bg-blue-100 text-blue-800',
                            'Structuring' => 'bg-orange-100 text-orange-800',
                            'Large_Amount' => 'bg-purple-100 text-purple-800',
                            'EDD_Required' => 'bg-purple-100 text-purple-800',
                            'Sanction_Match', 'Sanctions_Hit' => 'bg-red-100 text-red-800',
                            'Pep_Status' => 'bg-yellow-100 text-yellow-800',
                            'High_Risk_Customer', 'High_Risk_Country' => 'bg-red-100 text-red-800',
                            'Round_Amount', 'Profile_Deviation', 'Unusual_Pattern' => 'bg-gray-100 text-gray-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                        $typeLabel = $flag->flag_type->value;
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $typeClass }}">{{ $typeLabel }}</span>
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 max-w-xs overflow-hidden text-ellipsis">{{ $flag->flag_reason }}</td>
                    <td class="px-4 py-3 border-b border-gray-100">
                        @php
                        $statusClass = match($flag->status->value) {
                            'Open' => 'bg-red-100 text-red-800',
                            'Under_Review' => 'bg-orange-100 text-orange-800',
                            'Resolved' => 'bg-green-100 text-green-800',
                            default => 'bg-red-100 text-red-800'
                        };
                        $statusLabel = str_replace('_', ' ', $flag->status->value);
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100">{{ $flag->assignedTo->username ?? 'Unassigned' }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm text-gray-500">{{ $flag->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3 border-b border-gray-100">
                        <div class="flex gap-2">
                            @if($flag->transaction)
                            <a href="{{ route('transactions.show', $flag->transaction) }}" class="px-3 py-1.5 text-xs font-semibold bg-blue-600 text-white no-underline rounded hover:bg-blue-700 transition-colors">View</a>
                            @endif
                            @if(!$flag->alert)
                            <form action="{{ route('compliance.flags.generate-str', $flag) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">Generate STR</button>
                            </form>
                            @endif
                            @if(!$flag->status->isResolved())
                                @if(!$flag->assigned_to || $flag->assigned_to !== auth()->id())
                                <form action="{{ route('compliance.flags.assign', $flag) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors">Assign</button>
                                </form>
                                @endif
                                @if($flag->assigned_to === auth()->id())
                                <form action="{{ route('compliance.flags.resolve', $flag) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-green-600 text-white rounded hover:bg-green-700 transition-colors">Resolve</button>
                                </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex justify-center mt-6">
        {{ $flags->appends(request()->query())->links() }}
    </div>
@else
    <div class="text-center p-12 text-gray-500">
        <div class="text-5xl mb-4">🛡️</div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No Flagged Transactions</h3>
        <p><strong>No flagged transactions found.</strong><br>
        Great! Your compliance monitoring is working effectively.
        @if(request()->has('status') || request()->has('flag_type'))
            <br><a href="{{ route('compliance') }}" class="text-blue-600 no-underline hover:underline">Clear filters</a>
        @endif
        </p>
    </div>
    @endif
</div>
@endsection
