@extends('layouts.app')

@section('title', 'Edit Customer - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-2">Edit Customer: {{ e($customer->full_name) }}</h2>
    <p class="text-gray-500 text-sm">Update customer information and KYC details</p>
</div>

<!-- Customer Summary -->
<div class="bg-gray-50 border border-gray-200 rounded-md p-4 mb-6">
    <div class="flex justify-between py-2 border-b border-gray-200 last:border-b-0">
        <span class="font-semibold text-gray-600">Customer ID:</span>
        <span class="text-gray-800">{{ $customer->id }}</span>
    </div>
    <div class="flex justify-between py-2 border-b border-gray-200 last:border-b-0">
        <span class="font-semibold text-gray-600">Current Status:</span>
        <span class="text-gray-800">
            @if($customer->is_active ?? true)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-600">Inactive</span>
            @endif
        </span>
    </div>
    <div class="flex justify-between py-2 border-b border-gray-200 last:border-b-0">
        <span class="font-semibold text-gray-600">Current Risk Rating:</span>
        <span class="text-gray-800">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $customer->risk_rating === 'Low' ? 'bg-green-100 text-green-800' : ($customer->risk_rating === 'Medium' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                {{ $customer->risk_rating }}
            </span>
        </span>
    </div>
    <div class="flex justify-between py-2 border-b border-gray-200 last:border-b-0">
        <span class="font-semibold text-gray-600">PEP Status:</span>
        <span class="text-gray-800">
            @if($customer->pep_status)
                <span class="text-red-600 font-semibold">Yes - Politically Exposed Person</span>
            @else
                No
            @endif
        </span>
    </div>
    <div class="flex justify-between py-2 last:border-b-0">
        <span class="font-semibold text-gray-600">Created:</span>
        <span class="text-gray-800">{{ $customer->created_at->format('Y-m-d H:i') }}</span>
    </div>
</div>

@if($errors->any())
<div class="p-4 mb-6 rounded bg-red-100 border-l-4 border-red-600 text-red-800" role="alert" aria-live="assertive">
    <strong>Please fix the following errors:</strong>
    <ul class="mt-2 ml-4 list-disc text-sm">
        @foreach($errors->all() as $error)
        <li>{{ e($error) }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('customers.update', $customer) }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Basic Information -->
    <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 mb-4">
                <label for="full_name" class="block mb-2 text-sm font-semibold text-gray-700">Full Name <span class="text-red-600">*</span></label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $customer->full_name) }}" required class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('full_name')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="id_type" class="block mb-2 text-sm font-semibold text-gray-700">ID Type <span class="text-red-600">*</span></label>
                <select id="id_type" name="id_type" required class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                    @foreach($idTypes as $key => $label)
                        <option value="{{ $key }}" {{ old('id_type', $customer->id_type) == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('id_type')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="id_number" class="block mb-2 text-sm font-semibold text-gray-700">ID Number <span class="text-red-600">*</span></label>
                <input type="text" id="id_number" name="id_number" value="{{ old('id_number', $decryptedIdNumber) }}" required class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                <div class="text-gray-500 text-xs mt-1">MyKad format: XXXXXX-XX-XXXX for Malaysian IC</div>
                @error('id_number')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="date_of_birth" class="block mb-2 text-sm font-semibold text-gray-700">Date of Birth <span class="text-red-600">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth->format('Y-m-d')) }}" required class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('date_of_birth')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="nationality" class="block mb-2 text-sm font-semibold text-gray-700">Nationality <span class="text-red-600">*</span></label>
                <select id="nationality" name="nationality" required class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                    @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" {{ old('nationality', $customer->nationality) == $nat ? 'selected' : '' }}>
                            {{ $nat }}
                        </option>
                    @endforeach
                </select>
                @error('nationality')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Contact Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 mb-4">
                <label for="address" class="block mb-2 text-sm font-semibold text-gray-700">Address</label>
                <textarea id="address" name="address" rows="3" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">{{ old('address') }}</textarea>
                @error('address')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="phone" class="block mb-2 text-sm font-semibold text-gray-700">Contact Number</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g., 60123456789" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                <div class="text-gray-500 text-xs mt-1">Malaysian format: 60123456789 or +60123456789</div>
                @error('phone')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="email" class="block mb-2 text-sm font-semibold text-gray-700">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('email')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Employment Information -->
    <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Employment Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label for="occupation" class="block mb-2 text-sm font-semibold text-gray-700">Occupation</label>
                <input type="text" id="occupation" name="occupation" value="{{ old('occupation', $customer->occupation) }}" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('occupation')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="employer_name" class="block mb-2 text-sm font-semibold text-gray-700">Employer Name</label>
                <input type="text" id="employer_name" name="employer_name" value="{{ old('employer_name', $customer->employer_name) }}" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('employer_name')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="md:col-span-2 mb-4">
                <label for="employer_address" class="block mb-2 text-sm font-semibold text-gray-700">Employer Address</label>
                <textarea id="employer_address" name="employer_address" rows="2" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">{{ old('employer_address', $customer->employer_address) }}</textarea>
                @error('employer_address')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Risk & Compliance -->
    <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Risk Assessment</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label for="risk_rating" class="block mb-2 text-sm font-semibold text-gray-700">Risk Rating <span class="text-red-600">*</span></label>
                <select id="risk_rating" name="risk_rating" required class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                    @foreach($riskRatings as $rating)
                        <option value="{{ $rating }}" {{ old('risk_rating', $customer->risk_rating) == $rating ? 'selected' : '' }}>
                            {{ $rating }}
                        </option>
                    @endforeach
                </select>
                @error('risk_rating')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block mb-4">&nbsp;</label>
                <div class="flex items-center gap-2 mb-4">
                    <input type="checkbox" id="pep_status" name="pep_status" value="1" {{ old('pep_status', $customer->pep_status) ? 'checked' : '' }} class="w-auto">
                    <label for="pep_status" class="text-sm font-normal text-gray-700 cursor-pointer">Politically Exposed Person (PEP)</label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block mb-4">&nbsp;</label>
                <div class="flex items-center gap-2 mb-4">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }} class="w-auto">
                    <label for="is_active" class="text-sm font-normal text-gray-700 cursor-pointer">Customer is active</label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-4 mt-6">
        <a href="{{ route('customers.show', $customer) }}" class="px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold hover:bg-gray-300 transition-colors">Cancel</a>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-semibold hover:bg-blue-700 transition-colors">Update Customer</button>
    </div>
</form>
@endsection
