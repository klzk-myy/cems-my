@extends('layouts.app')

@section('title', 'EDD Templates')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">EDD Workflow Templates</h1>
    </div>
</div>

<div class="card">
    <table class="data-table">
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
                    <a href="{{ route('compliance.edd-templates.show', $template->id) }}" class="btn btn--primary btn--sm">View</a>
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