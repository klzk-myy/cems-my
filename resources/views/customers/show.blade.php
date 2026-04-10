@extends('layouts.app')

@section('title', 'Customer Profile - CEMS-MY')

@section('content')
<div class="mb-6 flex justify-between items-start flex-wrap gap-4">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 mb-1">{{ $customer->full_name }}</h2>
        <p class="text-gray-500 text-sm">Customer ID: {{ $customer->id }} | Created: {{ $customer->created_at->format('Y-m-d H:i') }}</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('customers.edit', $customer) }}" class="px-3 py-1.5 text-xs font-medium bg-orange-500 text-white no-underline rounded hover:bg-orange-600 transition-colors">Edit</a>
        <a href="{{ route('customers.kyc', $customer) }}" class="px-3 py-1.5 text-xs font-medium bg-blue-600 text-white no-underline rounded hover:bg-blue-700 transition-colors">KYC Documents</a>
        <a href="{{ route('customers.index') }}" class="px-3 py-1.5 text-xs font-medium bg-gray-200 text-gray-700 no-underline rounded hover:bg-gray-300 transition-colors">Back to List</a>
    </div>
</div>

<!-- Risk Warning -->
@if($customer->risk_rating === 'High' || $customer->pep_status)
    <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r mb-6">
        <h4 class="text-orange-800 font-semibold mb-2">High Risk Customer Alert</h4>
        @if($customer->risk_rating === 'High')
            <p class="text-orange-900 text-sm">This customer has been flagged as <strong>High Risk</strong>. All transactions require enhanced due diligence (EDD) and manager approval.</p>
        @endif
        @if($customer->pep_status)
            <p class="text-orange-900 text-sm mt-2">Customer is a <strong>Politically Exposed Person (PEP)</strong>. Additional monitoring and approval requirements apply.</p>
        @endif
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Basic Information -->
    <div class="bg-white rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Basic Information</h3>
        <table class="w-full">
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium w-2/5 text-sm">Full Name</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->full_name }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">ID Type</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->id_type }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">ID Number</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ Str::limit($customer->id_number_encrypted, 8, '****') }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">Date of Birth</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->date_of_birth->format('Y-m-d') }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">Nationality</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->nationality }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">Risk Rating</th>
                <td class="py-2.5">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $customer->risk_rating === 'Low' ? 'bg-green-100 text-green-800' : ($customer->risk_rating === 'Medium' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                        {{ $customer->risk_rating }}
                    </span>
                </td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">Risk Score</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->risk_score ?? 0 }} / 100</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">PEP Status</th>
                <td class="py-2.5">
                    @if($customer->pep_status)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase bg-red-100 text-red-800">PEP</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase bg-gray-200 text-gray-600">Non-PEP</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">Status</th>
                <td class="py-2.5">
                    @if($customer->is_active ?? true)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-600">Inactive</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Contact Information -->
    <div class="bg-white rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Contact Information</h3>
        <table class="w-full">
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium w-2/5 text-sm">Address</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->address ?? 'Not provided' }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">Phone</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->phone ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">Email</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->email ?? 'Not provided' }}</td>
            </tr>
        </table>
    </div>

    <!-- Employment Information -->
    <div class="bg-white rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Employment Information</h3>
        <table class="w-full">
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium w-2/5 text-sm">Occupation</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->occupation ?? 'Not provided' }}</td>
            </tr>
            <tr class="border-b border-gray-100">
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">Employer Name</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->employer_name ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <th class="py-2.5 pr-4 text-left text-gray-500 font-medium text-sm">Employer Address</th>
                <td class="py-2.5 text-gray-800 text-sm">{{ $customer->employer_address ?? 'Not provided' }}</td>
            </tr>
        </table>
    </div>

    <!-- Transaction Summary -->
    <div class="bg-white rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Transaction Summary</h3>
        <div class="grid grid-cols-3 gap-4 mt-4">
            <div class="text-center p-4 bg-gray-50 rounded">
                <div class="text-2xl font-bold text-gray-800">{{ $transactionStats['total_transactions'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Transactions</div>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded">
                <div class="text-2xl font-bold text-gray-800">RM {{ number_format($transactionStats['total_volume'], 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Volume</div>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded">
                <div class="text-2xl font-bold text-gray-800">RM {{ number_format($transactionStats['avg_transaction'], 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">Avg Transaction</div>
            </div>
        </div>
        @if($transactionStats['last_transaction'])
            <p class="mt-4 text-sm text-gray-500">
                Last transaction: {{ $transactionStats['last_transaction']->diffForHumans() }}
            </p>
        @endif
        <div class="mt-4">
            <a href="{{ route('customers.history', $customer) }}" class="px-3 py-1.5 text-xs font-medium bg-blue-600 text-white no-underline rounded hover:bg-blue-700 transition-colors">View Full History</a>
        </div>
    </div>
</div>

<!-- KYC Document Status -->
<div class="bg-white rounded-lg p-6 shadow-sm mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">KYC Document Status</h3>
    <div class="flex gap-6">
        <div class="text-sm">
            <strong class="text-gray-800">{{ $documentStatus['total'] }}</strong> <span class="text-gray-500">Total Documents</span>
        </div>
        <div class="text-sm">
            <strong class="text-green-600">{{ $documentStatus['verified'] }}</strong> <span class="text-gray-500">Verified</span>
        </div>
        <div class="text-sm">
            <strong class="text-orange-500">{{ $documentStatus['pending'] }}</strong> <span class="text-gray-500">Pending Verification</span>
        </div>
        @if($documentStatus['expired'] > 0)
        <div class="text-sm">
            <strong class="text-red-600">{{ $documentStatus['expired'] }}</strong> <span class="text-gray-500">Expired</span>
        </div>
        @endif
    </div>
    <div class="mt-4">
        <a href="{{ route('customers.kyc', $customer) }}" class="px-3 py-1.5 text-xs font-medium bg-green-600 text-white no-underline rounded hover:bg-green-700 transition-colors">Manage KYC Documents</a>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-lg p-6 shadow-sm mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">Recent Transactions</h3>
    @if($customer->transactions->count() > 0)
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">ID</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Type</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Currency</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Amount</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Rate</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Status</th>
                    <th class="text-left px-4 py-3 bg-gray-50 font-semibold text-gray-600 border-b border-gray-200">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customer->transactions as $transaction)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">{{ $transaction->id }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">{{ $transaction->type->value ?? $transaction->type }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">{{ $transaction->currency_code }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">RM {{ number_format($transaction->amount_local, 2) }}</td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">{{ $transaction->rate }}</td>
                    <td class="px-4 py-3 border-b border-gray-100">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ strtolower($transaction->status->value ?? $transaction->status) === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ $transaction->status->label() ?? $transaction->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 border-b border-gray-100 text-sm">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-center p-8 text-gray-500">No transactions found for this customer.</p>
    @endif
</div>
@endsection
