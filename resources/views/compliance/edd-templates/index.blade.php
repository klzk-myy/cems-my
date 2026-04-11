@extends('layouts.app')

@section('title', 'EDD Templates')

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
            <span class="breadcrumbs__text">EDD Templates</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-header__title">EDD Workflow Templates</h1>
</div>

<div class="card">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Status</th>
                <th>Questions</th>
                <th>Usage Count</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($templates as $template)
            <tr>
                <td>{{ $template->name }}</td>
                <td>
                    <span class="status-badge status-badge--active">
                        {{ $template->type->label() }}
                    </span>
                </td>
                <td>
                    <span class="status-badge {{ $template->is_active ? 'status-badge--active' : 'status-badge--inactive' }}">
                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>{{ $template->getTotalQuestions() }}</td>
                <td>{{ $template->enhanced_diligence_records_count ?? 0 }}</td>
                <td>
                    <a href="{{ route('compliance.edd-templates.show', $template->id) }}" class="btn btn-primary btn-sm">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-gray-500 py-8">No templates created yet</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection