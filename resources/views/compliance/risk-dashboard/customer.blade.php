@extends('layouts.base')

@section('title', 'Risk: ' . ($customer->full_name ?? ''))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $customer->full_name ?? 'Customer' }}</h3>
        @php $riskClass = match($customer->risk_level ?? '') { 'High' => 'badge-danger', 'Medium' => 'badge-warning', default => 'badge-success' }; @endphp
        <span class="badge {{ $riskClass }}">{{ $customer->risk_level ?? 'Unknown' }}</span>
    </div>
    <div class="card-body">
        <p><strong>IC Number:</strong> {{ $customer->ic_number ?? 'N/A' }}</p>
        <p><strong>Risk Score:</strong> {{ $customer->risk_score ?? 'N/A' }}</p>
        <p><strong>CDD Level:</strong> {{ $customer->cdd_level->label() ?? 'N/A' }}</p>
    </div>
</div>
@endsection
