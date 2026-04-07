@extends('layouts.app')

@section('title', 'Enhanced Due Diligence - CEMS-MY')

@section('content')
<div class="compliance-header">
    <h2>Enhanced Due Diligence (EDD)</h2>
    <p>Document source of funds and transaction purpose for high-risk customers</p>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>EDD Records</h4>
        <a href="{{ route('compliance.edd.create') }}" class="btn btn-primary">New EDD Record</a>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>EDD Reference</th>
                    <th>Customer</th>
                    <th>Risk Level</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                <tr>
                    <td><strong>{{ $record->edd_reference }}</strong></td>
                    <td>{{ $record->customer->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge bg-{{ $record->risk_level === 'Critical' ? 'danger' : ($record->risk_level === 'High' ? 'warning' : 'info') }}">
                            {{ $record->risk_level }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $record->status === 'Approved' ? 'success' : ($record->status === 'Pending_Review' ? 'warning' : 'secondary') }}">
                            {{ str_replace('_', ' ', $record->status) }}
                        </span>
                    </td>
                    <td>{{ $record->created_at->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('compliance.edd.show', $record) }}" class="btn btn-sm btn-info">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-muted">No EDD records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $records->links() }}
    </div>
</div>
@endsection