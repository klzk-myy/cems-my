@extends('layouts.app')

@section('title', 'Branches - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Branches</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Branches</h1>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('branches.create') }}" class="btn btn-primary">+ Add New Branch</a>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $branches->total() }}</div>
        <div class="stat-card__label">Total Branches</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $branches->where('is_active', true)->count() }}</div>
        <div class="stat-card__label">Active</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $branches->where('is_main', true)->count() }}</div>
        <div class="stat-card__label">Head Offices</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">{{ $branches->where('type', 'branch')->count() }}</div>
        <div class="stat-card__label">Branches</div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if(session('success'))
            <div class="p-4 bg-green-50 border-b border-green-200">
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-red-50 border-b border-red-200">
                <div class="alert alert-error mb-0">{{ session('error') }}</div>
            </div>
        @endif

        @if($branches->count() > 0)
        <table class="data-table">
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
                                'head_office' => 'status-badge--primary',
                                'branch' => 'status-badge--active',
                                default => 'status-badge--inactive'
                            };
                            $typeLabel = match($branch->type) {
                                'head_office' => 'Head Office',
                                'branch' => 'Branch',
                                default => 'Sub-Branch'
                            };
                        @endphp
                        <span class="status-badge {{ $typeClass }}">{{ $typeLabel }}</span>
                    </td>
                    <td>{{ $branch->city ?: '-' }}</td>
                    <td>
                        @if($branch->is_active)
                            <span class="status-badge status-badge--active">Active</span>
                        @else
                            <span class="status-badge status-badge--inactive">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($branch->is_main)
                            <span class="status-badge status-badge--success">Main</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('branches.show', $branch) }}" class="btn btn--primary btn--sm">View</a>
                            <a href="{{ route('branches.edit', $branch) }}" class="btn btn--secondary btn--sm">Edit</a>
                            @if($branch->is_active && !$branch->is_main)
                                <form action="{{ route('branches.destroy', $branch) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm" onclick="return confirm('Are you sure you want to deactivate this branch?');">
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
        @else
        <div class="p-12 text-center">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Branches</h3>
            <p class="text-gray-500">No branches have been created yet.</p>
            <a href="{{ route('branches.create') }}" class="btn btn--primary mt-4">+ Create First Branch</a>
        </div>
        @endif
    </div>
    @if($branches->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $branches->links() }}
    </div>
    @endif
</div>
@endsection