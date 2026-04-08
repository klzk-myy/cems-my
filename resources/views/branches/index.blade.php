@extends('layouts.app')

@section('title', 'Branch Management - CEMS-MY')

@section('styles')
<style>
    .branches-header {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .branches-header h2 {
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    .branches-header p {
        color: #718096;
        font-size: 0.875rem;
    }

    .branch-type-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .type-head_office { background: #e9d8fd; color: #6b46c1; }
    .type-branch { background: #c6f6d5; color: #276749; }
    .type-sub_branch { background: #feebc8; color: #c05621; }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-active { background: #c6f6d5; color: #276749; }
    .status-inactive { background: #e2e8f0; color: #718096; }

    .main-badge {
        display: inline-block;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.625rem;
        font-weight: 700;
        background: #3182ce;
        color: white;
        text-transform: uppercase;
    }

    .actions { display: flex; gap: 0.5rem; }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .pagination a, .pagination span {
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
    }
    .pagination a { background: #e2e8f0; color: #4a5568; }
    .pagination span { background: #3182ce; color: white; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a365d;
    }
    .stat-card .stat-label {
        font-size: 0.75rem;
        color: #718096;
        margin-top: 0.25rem;
    }
</style>
@endsection

@section('content')
<div class="branches-header">
    <div>
        <h2>Branch Management</h2>
        <p>Manage branches, head offices, and sub-branches</p>
    </div>
    <a href="{{ route('branches.create') }}" class="btn btn-success">+ Add New Branch</a>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 1rem;">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error" style="margin-bottom: 1rem;">
        {{ session('error') }}
    </div>
@endif

<div class="card">
    <h2>All Branches ({{ $branches->total() }})</h2>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>City</th>
                <th>Status</th>
                <th>Main</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($branches as $branch)
            <tr>
                <td><strong>{{ $branch->code }}</strong></td>
                <td>{{ $branch->name }}</td>
                <td>
                    @php
                        $typeClass = match($branch->type) {
                            'head_office' => 'type-head_office',
                            'branch' => 'type-branch',
                            default => 'type-sub_branch'
                        };
                        $typeLabel = match($branch->type) {
                            'head_office' => 'Head Office',
                            'branch' => 'Branch',
                            default => 'Sub-Branch'
                        };
                    @endphp
                    <span class="branch-type-badge {{ $typeClass }}">{{ $typeLabel }}</span>
                </td>
                <td>{{ $branch->city ?: '-' }}</td>
                <td>
                    @if($branch->is_active)
                        <span class="status-badge status-active">Active</span>
                    @else
                        <span class="status-badge status-inactive">Inactive</span>
                    @endif
                </td>
                <td>
                    @if($branch->is_main)
                        <span class="main-badge">Main</span>
                    @else
                        <span style="color: #a0aec0;">-</span>
                    @endif
                </td>
                <td>
                    <div class="actions">
                        <a href="{{ route('branches.show', $branch) }}" class="btn btn-primary btn-sm">View</a>
                        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-secondary btn-sm">Edit</a>
                        @if($branch->is_active && !$branch->is_main)
                            <form action="{{ route('branches.destroy', $branch) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to deactivate this branch?');">
                                    Deactivate
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination">
        {{ $branches->links() }}
    </div>
</div>
@endsection