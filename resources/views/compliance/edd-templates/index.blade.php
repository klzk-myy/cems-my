@extends('layouts.app')

@section('title', 'EDD Templates')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">EDD Workflow Templates</h1>

    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm">Name</th>
                    <th class="px-4 py-2 text-left text-sm">Type</th>
                    <th class="px-4 py-2 text-left text-sm">Status</th>
                    <th class="px-4 py-2 text-left text-sm">Questions</th>
                    <th class="px-4 py-2 text-left text-sm">Usage Count</th>
                    <th class="px-4 py-2 text-left text-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $template->name }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">
                            {{ $template->type->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-2">
                        @if($template->is_active)
                            <span class="text-green-600">Active</span>
                        @else
                            <span class="text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $template->getTotalQuestions() }}</td>
                    <td class="px-4 py-2">{{ $template->enhanced_diligence_records_count ?? 0 }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('compliance.edd-templates.show', $template->id) }}" class="text-blue-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No templates created yet</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection