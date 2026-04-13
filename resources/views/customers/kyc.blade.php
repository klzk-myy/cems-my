@extends('layouts.base')

@section('title', 'Customer KYC')

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Customer KYC Information</h3></div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-sm font-medium text-[--color-ink-muted] mb-4">Basic Information</h4>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-[--color-ink-muted]">Name</dt>
                        <dd class="font-medium">{{ $customer->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-[--color-ink-muted]">IC Number</dt>
                        <dd class="font-mono">{{ $customer->ic_number ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-[--color-ink-muted]">Risk Level</dt>
                        <dd>
                            @if(isset($customer->risk_level))
                                @statuslabel($customer->risk_level)
                            @else
                                <span class="text-[--color-ink-muted]">N/A</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-[--color-ink-muted]">CDD Level</dt>
                        <dd>
                            @if(isset($customer->cdd_level))
                                @statuslabel($customer->cdd_level)
                            @else
                                <span class="text-[--color-ink-muted]">N/A</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
            <div>
                <h4 class="text-sm font-medium text-[--color-ink-muted] mb-4">Documents</h4>
                <div class="space-y-3">
                    @forelse($documents ?? [] as $doc)
                    <div class="flex items-center gap-3 p-3 bg-[--color-surface-elevated] rounded">
                        <span class="font-mono text-sm">{{ $doc['type'] ?? 'N/A' }}</span>
                        <span class="text-[--color-ink-muted]">{{ $doc['uploaded_at'] ?? '' }}</span>
                    </div>
                    @empty
                    <p class="text-[--color-ink-muted] text-sm">No documents uploaded</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection