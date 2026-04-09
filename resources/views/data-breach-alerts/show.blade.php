@extends('layouts.app')

@section('title', "Data Breach Alert #{$dataBreachAlert->id} - CEMS-MY")

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Alert #{{ $dataBreachAlert->id }}</h1>
        <p class="text-sm text-gray-500">{{ $dataBreachAlert->created_at->format('Y-m-d H:i:s') }}</p>
    </div>
    <div>
        <span class="badge badge-{{ strtolower($dataBreachAlert->severity) }}">{{ $dataBreachAlert->severity }}</span>
        @if($dataBreachAlert->is_resolved)
        <span class="ml-2 badge badge-success">Resolved</span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header"><h3>Details</h3></div>
        <div class="card-body">
            <dl class="grid grid-cols-2 gap-4">
                <div><dt class="text-sm text-gray-500">Type</dt><dd>{{ $dataBreachAlert->alert_type }}</dd></div>
                <div><dt class="text-sm text-gray-500">Severity</dt><dd>{{ $dataBreachAlert->severity }}</dd></div>
                <div><dt class="text-sm text-gray-500">User ID</dt><dd>{{ $dataBreachAlert->triggered_by }}</dd></div>
                <div><dt class="text-sm text-gray-500">IP Address</dt><dd>{{ $dataBreachAlert->ip_address }}</dd></div>
                <div><dt class="text-sm text-gray-500">Record Count</dt><dd>{{ $dataBreachAlert->record_count ?? '-' }}</dd></div>
                <div class="col-span-2"><dt class="text-sm text-gray-500">Description</dt><dd>{{ $dataBreachAlert->description }}</dd></div>
                @if($dataBreachAlert->is_resolved)
                <div><dt class="text-sm text-gray-500">Resolved At</dt><dd>{{ $dataBreachAlert->resolved_at?->format('Y-m-d H:i') }}</dd></div>
                <div><dt class="text-sm text-gray-500">Resolved By</dt><dd>{{ $dataBreachAlert->resolved_by }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
</div>

@if(!$dataBreachAlert->is_resolved)
<form action="{{ route('data-breach-alerts.resolve', $dataBreachAlert) }}" method="POST" class="mt-4">
    @csrf
    <button type="submit" class="btn btn-primary">Resolve Alert</button>
</form>
@endif
@endsection
