<x-layouts.app title="Data Breach Alerts">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Data Breach Alerts</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="flex gap-4 mb-6">
                <select name="is_resolved" class="form-select">
                    <option value="">All Alerts</option>
                    <option value="0">Unresolved</option>
                    <option value="1">Resolved</option>
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>User</th>
                        <th>IP Address</th>
                        <th>Records</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                    <tr>
                        <td>{{ $alert->alert_type }}</td>
                        <td><span class="badge badge-{{ strtolower($alert->severity) }}">{{ $alert->severity }}</span></td>
                        <td>{{ $alert->triggered_by }}</td>
                        <td>{{ $alert->ip_address }}</td>
                        <td>{{ $alert->record_count ?? '-' }}</td>
                        <td>{{ $alert->is_resolved ? 'Resolved' : 'Open' }}</td>
                        <td>{{ $alert->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('data-breach-alerts.show', $alert) }}" class="text-blue-600">View</a>
                            @if(!$alert->is_resolved)
                            <form action="{{ route('data-breach-alerts.resolve', $alert) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 ml-2">Resolve</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No alerts found</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $alerts->links() }}
        </div>
    </div>
</x-layouts.app>
