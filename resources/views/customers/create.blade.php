@extends('layouts.app')

@section('title', 'Create Customer - CEMS-MY')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-2">Create New Customer</h2>
    <p class="text-gray-500 text-sm">Add a new customer with KYC information for compliance tracking</p>
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

<form action="{{ route('customers.store') }}" method="POST">
    @csrf

    <!-- Basic Information -->
    <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 mb-4">
                <label for="full_name" class="block mb-2 text-sm font-semibold text-gray-700">Full Name <span class="text-red-600">*</span></label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required placeholder="As shown on ID document" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('full_name')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="id_type" class="block mb-2 text-sm font-semibold text-gray-700">ID Type <span class="text-red-600">*</span></label>
                <select id="id_type" name="id_type" required onchange="toggleIdHint(this.value)" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                    @foreach($idTypes as $key => $label)
                        <option value="{{ $key }}" {{ old('id_type') == $key ? 'selected' : '' }}>
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
                <input type="text" id="id_number" name="id_number" value="{{ old('id_number') }}" required placeholder="" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                <div id="id_hint" class="text-gray-500 text-xs mt-1">MyKad format: XXXXXX-XX-XXXX (e.g., 900123-01-2345)</div>
                @error('id_number')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="date_of_birth" class="block mb-2 text-sm font-semibold text-gray-700">Date of Birth <span class="text-red-600">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required max="{{ date('Y-m-d', strtotime('-18 years')) }}" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('date_of_birth')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="nationality" class="block mb-2 text-sm font-semibold text-gray-700">Nationality <span class="text-red-600">*</span></label>
                <select id="nationality" name="nationality" required class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                    @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" {{ old('nationality') == $nat ? 'selected' : '' }}>
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
                <textarea id="address" name="address" rows="3" placeholder="Full residential address" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">{{ old('address') }}</textarea>
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
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="customer@example.com" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('email')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Employment Information (Optional) -->
    <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Employment Information (Optional)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label for="occupation" class="block mb-2 text-sm font-semibold text-gray-700">Occupation</label>
                <input type="text" id="occupation" name="occupation" value="{{ old('occupation') }}" placeholder="e.g., Engineer, Business Owner" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('occupation')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="employer_name" class="block mb-2 text-sm font-semibold text-gray-700">Employer Name</label>
                <input type="text" id="employer_name" name="employer_name" value="{{ old('employer_name') }}" placeholder="Company or organization name" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                @error('employer_name')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="md:col-span-2 mb-4">
                <label for="employer_address" class="block mb-2 text-sm font-semibold text-gray-700">Employer Address</label>
                <textarea id="employer_address" name="employer_address" rows="2" placeholder="Employer business address" class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">{{ old('employer_address') }}</textarea>
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
                <label for="risk_rating" class="block mb-2 text-sm font-semibold text-gray-700">Initial Risk Rating <span class="text-red-600">*</span></label>
                <select id="risk_rating" name="risk_rating" required class="w-full p-3 border-2 border-gray-200 rounded-md text-base focus:outline-none focus:border-blue-500">
                    @foreach($riskRatings as $rating)
                        <option value="{{ $rating }}" {{ old('risk_rating') == $rating ? 'selected' : '' }}>
                            {{ $rating }}
                        </option>
                    @endforeach
                </select>
                <div class="text-gray-500 text-xs mt-1">
                    Risk will be automatically assessed based on nationality and PEP status
                </div>
                @error('risk_rating')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block mb-4">&nbsp;</label>
                <div class="flex items-center gap-2 mb-4">
                    <input type="checkbox" id="pep_status" name="pep_status" value="1" {{ old('pep_status') ? 'checked' : '' }} class="w-auto">
                    <label for="pep_status" class="text-sm font-normal text-gray-700 cursor-pointer">Politically Exposed Person (PEP)</label>
                </div>
                <div class="text-gray-500 text-xs mt-1">Check if customer holds or held a prominent public position</div>
            </div>
        </div>

        <div class="mt-4 p-4 bg-orange-50 rounded-md border-l-4 border-orange-500">
            <strong class="text-orange-800">Compliance Notice:</strong>
            <p class="mt-2 text-orange-900 text-sm">
                Upon submission, the customer's name will be automatically screened against sanctions lists.
                Customers from high-risk countries or with PEP status will be assigned higher risk ratings.
            </p>
        </div>
    </div>

    <div class="flex gap-4 mt-6">
        <a href="{{ route('customers.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold hover:bg-gray-300 transition-colors">Cancel</a>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded font-semibold hover:bg-green-700 transition-colors">Create Customer</button>
    </div>
</form>
@endsection

@section('scripts')
<script>
    function toggleIdHint(value) {
        const hint = document.getElementById('id_hint');
        const idInput = document.getElementById('id_number');

        if (value === 'MyKad') {
            hint.textContent = 'MyKad format: XXXXXX-XX-XXXX (e.g., 900123-01-2345)';
            idInput.placeholder = '900123-01-2345';
        } else if (value === 'Passport') {
            hint.textContent = 'Passport number as shown in passport';
            idInput.placeholder = 'AB123456';
        } else {
            hint.textContent = 'Enter ID number as shown on document';
            idInput.placeholder = '';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const idType = document.getElementById('id_type').value;
        toggleIdHint(idType);
    });
</script>
@endsection
