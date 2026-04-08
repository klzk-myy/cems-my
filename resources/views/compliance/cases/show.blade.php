@extends('layouts.app')

@section('title', 'Case Detail')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">Case {{ $case->case_number }}</h1>
            <p class="text-gray-600">{{ $case->case_type }} - {{ $case->status->label() }}</p>
        </div>
        <a href="{{ route('compliance.cases.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Back to Cases</a>
    </div>

    <div class="grid grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Case Information</h2>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Case Number</dt>
                    <dd class="font-medium">{{ $case->case_number }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Case Type</dt>
                    <dd class="font-medium">{{ $case->case_type }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Status</dt>
                    <dd>
                        <span class="px-2 py-1 rounded text-xs font-medium
                            @if($case->status->value === 'Open') bg-blue-100 text-blue-700
                            @elseif($case->status->value === 'UnderReview') bg-yellow-100 text-yellow-700
                            @elseif($case->status->value === 'Escalated') bg-red-100 text-red-700
                            @else bg-gray-100 text-gray-700
                            @endif">
                            {{ $case->status->label() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Priority</dt>
                    <dd>
                        <span class="px-2 py-1 rounded text-xs font-medium
                            @if($case->priority->value === 'critical') bg-red-100 text-red-700
                            @elseif($case->priority->value === 'high') bg-orange-100 text-orange-700
                            @else bg-gray-100 text-gray-700
                            @endif">
                            {{ $case->priority->label() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">SLA Deadline</dt>
                    <dd class="font-medium {{ $case->isOverdue() ? 'text-red-600' : '' }}">
                        {{ $case->sla_deadline?->format('Y-m-d H:i') ?? 'N/A' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Created</dt>
                    <dd class="font-medium">{{ $case->created_at->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Customer</h2>
            <dl class="grid grid-cols-1 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Customer</dt>
                    <dd class="font-medium">{{ $case->customer?->full_name ?? 'N/A' }}</dd>
                </div>
                @if($case->customer)
                <div>
                    <dt class="text-sm text-gray-500">Risk Rating</dt>
                    <dd class="font-medium">{{ $case->customer->risk_rating ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">PEP Status</dt>
                    <dd class="font-medium">{{ $case->customer->pep_status ? 'Yes' : 'No' }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Assignment</h2>
            <dl class="grid grid-cols-1 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Assigned To</dt>
                    <dd class="font-medium">{{ $case->assignedTo?->username ?? 'Unassigned' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Opened By</dt>
                    <dd class="font-medium">{{ $case->openedBy?->username ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @if($case->notes)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Case Summary</h2>
        <p class="text-gray-700">{{ $case->notes }}</p>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Linked Alerts</h2>
        @if($case->alerts && $case->alerts->count() > 0)
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Priority</th>
                    <th class="px-4 py-2 text-left text-sm">Type</th>
                    <th class="px-4 py-2 text-left text-sm">Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach($case->alerts as $alert)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $alert->priority->label() }}</td>
                    <td class="px-4 py-2">{{ $alert->type?->value ?? 'N/A' }}</td>
                    <td class="px-4 py-2">{{ Str::limit($alert->reason, 50) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-gray-500">No alerts linked to this case</p>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Actions</h2>
        <div class="flex gap-4">
            <form action="{{ route('compliance.cases.update', $case->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <select name="status" class="border rounded px-3 py-2">
                    <option value="Open" {{ $case->status->value === 'Open' ? 'selected' : '' }}>Open</option>
                    <option value="UnderReview" {{ $case->status->value === 'UnderReview' ? 'selected' : '' }}>Under Review</option>
                    <option value="Escalated" {{ $case->status->value === 'Escalated' ? 'selected' : '' }}>Escalated</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update Status</button>
            </form>
            @if($case->status->value !== 'Closed')
            <form action="{{ route('compliance.cases.escalate', $case->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Escalate</button>
            </form>
            @endif

            @if($case->status->value !== 'Closed')
            <button type="button" onclick="document.getElementById('mergeModal').classList.remove('hidden')" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                Merge Case
            </button>
            @endif
        </div>
    </div>

    <!-- Merge Modal -->
    <div id="mergeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Merge Case {{ $case->case_number }} Into Another Case</h3>
            <form action="{{ route('compliance.cases.merge', $case->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="target_case_id" class="block text-sm font-medium text-gray-700 mb-2">Target Case Number</label>
                    <input type="number" name="target_case_id" id="target_case_id" required
                           class="w-full border rounded px-3 py-2"
                           placeholder="Enter target case ID">
                    <p class="text-sm text-gray-500 mt-1">All alerts from this case will be moved to the target case, and this case will be closed.</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('mergeModal').classList.add('hidden')"
                            class="px-4 py-2 border rounded hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Merge Cases</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
