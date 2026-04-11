@extends('layouts.app')

@section('title', 'STR Studio - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item">
            <a href="{{ route('compliance') }}" class="breadcrumbs__link">Compliance</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">STR Studio</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-header__title">STR Studio</h1>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $summary['total_pending'] }}</div>
        <div class="stat-card__label">Total Pending</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['overdue'] }}</div>
        <div class="stat-card__label">Overdue</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $summary['due_24h'] }}</div>
        <div class="stat-card__label">Due in 24h</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">{{ $summary['due_48h'] }}</div>
        <div class="stat-card__label">Due in 48h</div>
    </div>
</div>

<div class="card">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Filing Deadline</th>
                <th>Confidence</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($drafts as $draft)
            <tr>
                <td>
                    <a href="{{ route('compliance.str-studio.show', $draft->id) }}" class="text-blue-600 hover:underline">
                        #{{ $draft->id }}
                    </a>
                </td>
                <td>{{ $draft->customer?->full_name ?? 'N/A' }}</td>
                <td class="{{ $draft->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                    {{ $draft->filing_deadline?->format('Y-m-d') ?? 'N/A' }}
                </td>
                <td>{{ $draft->confidence_score }}%</td>
                <td>{{ $draft->status->label() }}</td>
                <td>
                    <a href="{{ route('compliance.str-studio.show', $draft->id) }}" class="btn btn-primary btn-sm">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-gray-500 py-8">No STR drafts</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">
        {{ $drafts->links() }}
    </div>
</div>
@endsection