@extends('layouts.base')

@section('title', 'Audit Log')

@section('header-title')
<div>
    <h1 class="text-2xl font-semibold text-[--color-ink]">Audit Log</h1>
    <p class="text-sm text-[--color-ink-muted]">System activity and security events</p>
</div>
@endsection

@section('content')
{{-- Filters --}}
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="form-group mb-0">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-input" placeholder="Event or user..." value="{{ request('search') }}">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Event Type</label>
                <select name="event_type" class="form-select">
                    <option value="">All Events</option>
                    <option value="transaction" {{ request('event_type') === 'transaction' ? 'selected' : '' }}>Transaction</option>
                    <option value="user" {{ request('event_type') === 'user' ? 'selected' : '' }}>User</option>
                    <option value="compliance" {{ request('event_type') === 'compliance' ? 'selected' : '' }}>Compliance</option>
                    <option value="system" {{ request('event_type') === 'system' ? 'selected' : '' }}>System</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="btn btn-secondary">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

{{-- Audit Log Table --}}
<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Event</th>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs ?? [] as $log)
                <tr>
                    <td class="font-mono text-xs text-[--color-ink-muted]">
                        {{ $log->created_at->format('d M Y, H:i:s') }}
                    </td>
                    <td>
                        <span class="badge badge-default">{{ $log->event_type ?? 'System' }}</span>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-[--color-canvas-subtle] rounded flex items-center justify-center text-xs">
                                {{ substr($log->user->username ?? 'S', 0, 1) }}
                            </div>
                            <span class="text-sm">{{ $log->user->username ?? 'System' }}</span>
                        </div>
                    </td>
                    <td class="font-mono text-xs">{{ $log->ip_address ?? 'N/A' }}</td>
                    <td class="text-sm">{{ $log->description ?? 'No description' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-[--color-ink-muted]">No audit logs found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs && $logs->hasPages())
        <div class="card-footer">
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
