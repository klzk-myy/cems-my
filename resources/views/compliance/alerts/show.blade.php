@extends('layouts.app')

@section('title', 'Alert Detail')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">Alert Detail</h1>
            <p class="text-gray-600">Alert #{{ $alert->id }}</p>
        </div>
        <a href="{{ route('compliance.alerts.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Back to Alerts</a>
    </div>

    <div class="grid grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Alert Information</h2>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Priority</dt>
                    <dd>
                        <span class="px-2 py-1 rounded text-xs font-medium
                            @if($alert->priority->value === 'critical') bg-red-100 text-red-700
                            @elseif($alert->priority->value === 'high') bg-orange-100 text-orange-700
                            @elseif($alert->priority->value === 'medium') bg-yellow-100 text-yellow-700
                            @else bg-green-100 text-green-700
                            @endif">
                            {{ $alert->priority->label() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Type</dt>
                    <dd class="font-medium">{{ $alert->type?->value ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Risk Score</dt>
                    <dd class="font-medium">{{ $alert->risk_score }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Source</dt>
                    <dd class="font-medium">{{ $alert->source }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Status</dt>
                    <dd class="font-medium">{{ $alert->status?->label() ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Created</dt>
                    <dd class="font-medium">{{ $alert->created_at->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
            <dl class="grid grid-cols-1 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Customer</dt>
                    <dd class="font-medium">{{ $alert->customer?->full_name ?? 'N/A' }}</dd>
                </div>
                @if($alert->customer)
                <div>
                    <dt class="text-sm text-gray-500">Risk Rating</dt>
                    <dd class="font-medium">{{ $alert->customer->risk_rating ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">PEP Status</dt>
                    <dd class="font-medium">{{ $alert->customer->pep_status ? 'Yes' : 'No' }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Reason</h2>
        <p class="text-gray-700">{{ $alert->reason ?? 'No reason provided' }}</p>
    </div>

    @if($alert->assigned_to)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Assignment</h2>
        <p><strong>Assigned To:</strong> {{ $alert->assignedTo?->username ?? 'N/A' }}</p>
        <p><strong>Assigned At:</strong> {{ $alert->updated_at->format('Y-m-d H:i') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Actions</h2>
        <div class="flex gap-4">
            @if(!$alert->assigned_to)
            <form action="{{ route('compliance.alerts.assign', $alert->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Assign to Me</button>
            </form>
            @endif
            @if($alert->assigned_to && $alert->status?->value !== 'resolved')
            <form action="{{ route('compliance.alerts.resolve', $alert->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Resolve</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
