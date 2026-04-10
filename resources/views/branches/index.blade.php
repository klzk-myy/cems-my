@extends('layouts.app')

@section('title', 'Branch Management - CEMS-MY')

@section('content')
<div class="branches-header">
    <div>
        <h2>Branch Management</h2>
        <p>Manage branches, head offices, and sub-branches</p>
    </div>
    <a href="{{ route('branches.create') }}" class="btn btn-success">+ Add New Branch</a>
</div>

@if(session('success'))
    <div class="alert alert-success mb-4">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error mb-4">
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
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td>
                    <div class="actions">
                        <a href="{{ route('branches.show', $branch) }}" class="btn btn-primary btn-sm">View</a>
                        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-secondary btn-sm">Edit</a>
                        @if($branch->is_active && !$branch->is_main)
                            <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="inline">
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
