@extends('layouts.app')

@section('title', 'KYC Documents - CEMS-MY')

@section('content')
<div class="mb-6 flex justify-between items-start flex-wrap gap-4">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 mb-1">KYC Document Management</h2>
        <p class="text-gray-500 text-sm">Upload and verify customer identification documents</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('customers.show', $customer) }}" class="px-3 py-1.5 text-xs font-medium bg-gray-200 text-gray-700 no-underline rounded hover:bg-gray-300 transition-colors">Back to Profile</a>
        <a href="{{ route('customers.index') }}" class="px-3 py-1.5 text-xs font-medium bg-gray-200 text-gray-700 no-underline rounded hover:bg-gray-300 transition-colors">Back to List</a>
    </div>
</div>

<!-- Customer Summary -->
<div class="bg-white rounded-lg p-6 mb-6 shadow-sm flex justify-between items-center flex-wrap gap-4">
    <div class="flex gap-8 flex-wrap">
        <div class="flex flex-col">
            <span class="text-xs text-gray-500 uppercase">Customer Name</span>
            <span class="font-semibold text-gray-800">{{ $customer->full_name }}</span>
        </div>
        <div class="flex flex-col">
            <span class="text-xs text-gray-500 uppercase">ID Type</span>
            <span class="font-semibold text-gray-800">{{ $customer->id_type }}</span>
        </div>
        <div class="flex flex-col">
            <span class="text-xs text-gray-500 uppercase">Risk Rating</span>
            <span class="font-semibold">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $customer->risk_rating === 'Low' ? 'bg-green-100 text-green-800' : ($customer->risk_rating === 'Medium' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                    {{ $customer->risk_rating }}
                </span>
            </span>
        </div>
        <div class="flex flex-col">
            <span class="text-xs text-gray-500 uppercase">Documents</span>
            <span class="font-semibold text-gray-800">
                {{ $documents->where('verified_at', '!=', null)->count() }} / {{ $documents->count() }} Verified
            </span>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="p-4 mb-4 rounded bg-green-100 border-l-4 border-green-600 text-green-800">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="p-4 mb-4 rounded bg-red-100 border-l-4 border-red-600 text-red-800">{{ session('error') }}</div>
@endif

<!-- Document Upload Section -->
<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Upload New Document</h3>

    <div class="border-2 border-dashed border-gray-200 rounded-lg p-8 text-center hover:border-blue-500 hover:bg-gray-50 mb-6">
        <form action="{{ route('customers.kyc.upload', $customer) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4 text-left">
                <label for="document_type" class="block mb-2 text-sm font-semibold text-gray-700">Document Type <span class="text-red-600">*</span></label>
                <select id="document_type" name="document_type" required class="w-full p-3 border-2 border-gray-200 rounded-md text-base">
                    <option value="">Select document type...</option>
                    @foreach($documentTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('document_type')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4 text-left">
                <label for="document_file" class="block mb-2 text-sm font-semibold text-gray-700">Document File <span class="text-red-600">*</span></label>
                <input type="file" id="document_file" name="document_file" required accept=".jpg,.jpeg,.png,.pdf" class="w-full p-3 border-2 border-gray-200 rounded-md text-base">
                <div class="text-gray-500 text-xs mt-1">Accepted formats: JPG, PNG, PDF. Maximum file size: 10MB</div>
                @error('document_file')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-6 text-left">
                <label for="expiry_date" class="block mb-2 text-sm font-semibold text-gray-700">Expiry Date (Optional)</label>
                <input type="date" id="expiry_date" name="expiry_date" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full p-3 border-2 border-gray-200 rounded-md text-base">
                <div class="text-gray-500 text-xs mt-1">For passports and some ID cards. Leave blank if document doesn't expire.</div>
                @error('expiry_date')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded font-semibold hover:bg-green-700 transition-colors">Upload Document</button>
        </form>
    </div>

    @if($customer->id_type === 'MyKad')
    <div class="mt-6 p-4 bg-gray-50 rounded">
        <h4 class="text-sm font-semibold text-gray-800 mb-2">Required Documents for MyKad Customer:</h4>
        <ul class="list-disc ml-6 text-sm text-gray-600">
            <li class="mb-1">MyKad Front (Required)</li>
            <li class="mb-1">MyKad Back (Required)</li>
            <li>Proof of Address (If address differs from IC)</li>
        </ul>
    </div>
    @elseif($customer->id_type === 'Passport')
    <div class="mt-6 p-4 bg-gray-50 rounded">
        <h4 class="text-sm font-semibold text-gray-800 mb-2">Required Documents for Passport Customer:</h4>
        <ul class="list-disc ml-6 text-sm text-gray-600">
            <li class="mb-1">Passport (Required - must show photo and personal details page)</li>
            <li>Proof of Address (Required)</li>
        </ul>
    </div>
    @endif
</div>

<!-- Document List Section -->
<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Uploaded Documents ({{ $documents->count() }})</h3>

    @if($documents->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($documents as $document)
                <div class="border border-gray-200 rounded-lg p-4 relative {{ $document->isVerified() ? 'border-green-500 bg-green-50' : ($document->isExpired() ? 'border-red-500 bg-red-50' : 'border-orange-400 bg-orange-50') }}">
                    <div class="font-semibold text-gray-800 mb-2">
                        {{ $documentTypes[$document->document_type] ?? $document->document_type }}
                    </div>
                    <div class="text-xs text-gray-500 flex flex-col gap-1">
                        <span>Uploaded: {{ $document->created_at->format('Y-m-d H:i') }}</span>
                        @if($document->uploader)
                            <span>By: {{ $document->uploader->username }}</span>
                        @endif
                        <span>Size: {{ number_format($document->file_size / 1024, 2) }} KB</span>

                        @if($document->expiry_date)
                            <span>Expires: {{ $document->expiry_date->format('Y-m-d') }}</span>
                        @endif

                        @if($document->isVerified())
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold uppercase bg-green-100 text-green-800 mt-2">
                                Verified by {{ $document->verifier->username ?? 'Unknown' }} on {{ $document->verified_at->format('Y-m-d') }}
                            </span>
                        @elseif($document->isExpired())
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold uppercase bg-red-100 text-red-800 mt-2">Expired</span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold uppercase bg-orange-100 text-orange-800 mt-2">Pending Verification</span>
                        @endif
                    </div>

                    <div class="flex gap-2 mt-4">
                        @if(! $document->isVerified() && $canVerify)
                            <form action="{{ route('customers.kyc.verify', [$customer, $document]) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-green-600 text-white rounded hover:bg-green-700 transition-colors">Verify</button>
                            </form>
                        @endif

                        @if($document->isVerified())
                            <span class="text-green-600 text-xs self-center">Verified</span>
                        @endif

                        <form action="{{ route('customers.kyc.delete', [$customer, $document]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this document?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-red-600 text-white rounded hover:bg-red-700 transition-colors">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center p-12 text-gray-500">
            <p>No documents uploaded yet.</p>
            <p>Please upload the required KYC documents above.</p>
        </div>
    @endif
</div>

<!-- Verification Notice -->
@if($canVerify)
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r mb-6">
        <p class="text-blue-800 text-sm"><strong>Compliance Officer/Admin Notice:</strong> You have permission to verify customer documents. Please ensure all verified documents are genuine and legible before approving.</p>
    </div>
@else
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r mb-6">
        <p class="text-blue-800 text-sm"><strong>Note:</strong> Only Compliance Officers and Administrators can verify KYC documents. Please contact your supervisor after uploading all required documents.</p>
    </div>
@endif
@endsection
