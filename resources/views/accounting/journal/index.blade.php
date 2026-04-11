@extends('layouts.app')

@section('title', 'Journal Entries - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item">
            <a href="{{ route('accounting.index') }}" class="breadcrumbs__link">Accounting</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Journal</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Journal Entries</h1>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('accounting.journal.create') }}" class="btn btn--primary">+ Create Entry</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-6">{{ session('success') }}</div>
@endif

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $entries->total() }}</div>
        <div class="stat-card__label">Total Entries</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $entries->where('status', 'posted')->count() }}</div>
        <div class="stat-card__label">Posted</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $entries->where('status', 'draft')->count() }}</div>
        <div class="stat-card__label">Draft</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $entries->where('status', 'reversed')->count() }}</div>
        <div class="stat-card__label">Reversed</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">All Journal Entries</h3>
    </div>
    <div class="card-body p-0">
        @if($entries->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>Debits</th>
                    <th>Credits</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                <tr>
                    <td>{{ $entry->id }}</td>
                    <td>{{ $entry->entry_date }}</td>
                    <td>{{ $entry->reference_type }} {{ $entry->reference_id }}</td>
                    <td>{{ Str::limit($entry->description, 50) }}</td>
                    <td class="text-right">{{ number_format($entry->getTotalDebits(), 2) }}</td>
                    <td class="text-right">{{ number_format($entry->getTotalCredits(), 2) }}</td>
                    <td>
                        @if($entry->isPosted())
                            <span class="status-badge status-badge--active">Posted</span>
                        @elseif($entry->isReversed())
                            <span class="status-badge status-badge--danger">Reversed</span>
                        @else
                            <span class="status-badge status-badge--inactive">Draft</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('accounting.journal.show', $entry) }}" class="btn btn--primary btn--sm">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-12 text-center">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Journal Entries</h3>
            <p class="text-gray-500">No journal entries found.</p>
            <a href="{{ route('accounting.journal.create') }}" class="btn btn--primary mt-4">+ Create First Entry</a>
        </div>
        @endif
    </div>
    @if($entries->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $entries->links() }}
    </div>
    @endif
</div>
@endsection