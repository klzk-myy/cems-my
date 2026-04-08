@extends('layouts.app')

@section('title', 'Create STR Draft')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Create STR Draft</h1>
        <p class="text-gray-600">Case #{{ $case->case_number }} - {{ $case->customer->full_name }}</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('compliance.str-studio.store') }}" method="POST">
            @csrf

            <input type="hidden" name="case_id" value="{{ $case->id }}">
            <input type="hidden" name="customer_id" value="{{ $case->customer->id }}">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                <p class="text-gray-900">{{ $case->customer->full_name }}</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Linked Case</label>
                <p class="text-gray-900">#{{ $case->case_number }} - {{ $case->case_type }}</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Suspected Activity</label>
                <select name="suspected_activity" class="w-full border rounded px-3 py-2">
                    <option value="">Select activity type</option>
                    <option value="Structuring">Structuring</option>
                    <option value="Money Laundering">Money Laundering</option>
                    <option value="Terrorist Financing">Terrorist Financing</option>
                    <option value="Fraud">Fraud</option>
                    <option value="Tax Evasion">Tax Evasion</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Narrative</label>
                <textarea name="narrative" rows="8" class="w-full border rounded px-3 py-2" placeholder="Describe the suspicious activity...">{{ old('narrative') }}</textarea>
                @error('narrative')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('compliance.str-studio.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create Draft</button>
            </div>
        </form>
    </div>
</div>
@endsection
