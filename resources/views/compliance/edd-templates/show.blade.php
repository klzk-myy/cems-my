@extends('layouts.app')

@section('title', 'EDD Template - ' . $template->name)

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">{{ $template->name }}</h1>
            <p class="text-gray-600">Version {{ $template->version }} - {{ $template->type->label() }}</p>
        </div>
        <div class="flex gap-4">
            <form action="{{ route('compliance.edd-templates.duplicate', $template->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 border rounded hover:bg-gray-50">Duplicate</button>
            </form>
            <a href="{{ route('compliance.edd-templates.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Template Information</h2>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Name</dt>
                    <dd class="font-medium">{{ $template->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Type</dt>
                    <dd class="font-medium">{{ $template->type->label() }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Version</dt>
                    <dd class="font-medium">{{ $template->version }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Status</dt>
                    <dd>
                        <span class="px-2 py-1 rounded text-xs font-medium {{ $template->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Questions</dt>
                    <dd class="font-medium">{{ $template->getTotalQuestions() }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Created By</dt>
                    <dd class="font-medium">{{ $template->createdBy?->username ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Usage Statistics</h2>
            <dl class="grid grid-cols-1 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Times Used</dt>
                    <dd class="font-medium text-2xl">{{ $template->enhancedDiligenceRecords->count() }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
            <form action="{{ route('compliance.edd-templates.update', $template->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }} class="mr-2">
                        <span class="text-sm">Active</span>
                    </label>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update Status</button>
            </form>
        </div>
    </div>

    @if($template->description)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Description</h2>
        <p class="text-gray-700">{{ $template->description }}</p>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Questions</h2>
        @php $sections = $template->getSections(); @endphp
        @if($sections && count($sections) > 0)
            @foreach($sections as $sectionIndex => $section)
            <div class="mb-6 border-b pb-4 last:border-b-0">
                <h3 class="text-md font-medium mb-3">{{ $section['title'] ?? 'Section ' . ($sectionIndex + 1) }}</h3>
                <ul class="list-disc list-inside space-y-2">
                    @foreach($section['questions'] ?? [] as $question)
                    <li class="text-gray-700">{{ $question['text'] ?? $question['question'] ?? 'Question' }}</li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        @else
        <p class="text-gray-500">No questions defined in this template</p>
        @endif
    </div>
</div>
@endsection
