<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Transaction #{{ $transaction->id }} - CEMS-MY</title>
</head>
<body class="font-sans bg-gray-100 text-gray-800 m-0 p-0">
    <header class="bg-blue-900 text-white px-8 py-4 flex justify-between items-center">
        <h1 class="text-xl font-semibold">CEMS-MY Transaction Confirmation</h1>
        <nav class="flex gap-4">
            <a href="/" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Dashboard</a>
            <a href="/transactions" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Transactions</a>
            <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Logout</a>
        </nav>
        <form id="logout-form" action="/logout" method="POST" class="hidden">
            @csrf
        </form>
    </header>

    <div class="max-w-3xl mx-auto my-8 px-4">
        <div class="bg-gradient-to-br from-blue-900 to-blue-700 text-white rounded-lg p-8 mb-6 text-center">
            <h3 class="text-2xl font-semibold mb-2">Large Transaction Confirmation Required</h3>
            <div class="text-4xl font-bold my-4">RM {{ number_format($transaction->amount_local, 2) }}</div>
            <div class="opacity-90">
                This transaction exceeds RM 50,000 and requires manager confirmation before completion.
            </div>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">{{ session('error') }}</div>
        @endif

        @if($confirmation->isExpired())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <strong>Confirmation Expired</strong><br>
                This confirmation request has expired. Please request a new confirmation.
            </div>
        @endif

        <!-- Transaction Details -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-blue-900 mb-4 border-b-2 border-gray-200 pb-2">Transaction Details</h2>
            <div class="divide-y divide-gray-200">
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Transaction ID</span>
                    <span class="text-gray-800 font-semibold">#{{ $transaction->id }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Type</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->type->value }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Currency</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->currency_code }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Foreign Amount</span>
                    <span class="text-gray-800 font-semibold">{{ number_format($transaction->amount_foreign, 4) }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Exchange Rate</span>
                    <span class="text-gray-800 font-semibold">{{ number_format($transaction->rate, 6) }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Local Amount (MYR)</span>
                    <span class="text-gray-800 font-semibold">RM {{ number_format($transaction->amount_local, 2) }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">CDD Level</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->cdd_level }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Purpose</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->purpose }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Source of Funds</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->source_of_funds }}</span>
                </div>
            </div>
        </div>

        <!-- Customer Details -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-blue-900 mb-4 border-b-2 border-gray-200 pb-2">Customer Information</h2>
            <div class="divide-y divide-gray-200">
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Name</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->customer->full_name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">ID Type</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->customer->id_type ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">ID Number</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->customer->id_number ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Risk Rating</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->customer->risk_rating ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Confirmation Form -->
        @if(!$confirmation->isExpired() && $confirmation->status === 'pending')
            <div class="bg-white rounded-lg shadow-sm p-8">
                <h2 class="text-center text-xl font-semibold text-gray-800 mb-6">Manager Confirmation</h2>

                <form action="/transactions/{{ $transaction->id }}/confirm" method="POST">
                    @csrf

                    <div class="mb-6">
                        <label for="notes" class="block mb-2 font-semibold text-gray-800">Confirmation Notes (Optional)</label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="3"
                            placeholder="Add any notes regarding this confirmation..."
                            class="w-full p-3 border border-gray-200 rounded font-sans resize-y text-sm"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex gap-4 justify-center">
                        <button type="submit" name="confirmation_action" value="confirm" class="px-6 py-3 bg-green-600 text-white rounded font-semibold hover:bg-green-700 transition-colors cursor-pointer border-0" onclick="return confirm('Are you sure you want to CONFIRM this transaction?');">
                            Confirm Transaction
                        </button>
                        <button type="submit" name="confirmation_action" value="reject" class="px-6 py-3 bg-red-600 text-white rounded font-semibold hover:bg-red-700 transition-colors cursor-pointer border-0" onclick="return confirm('Are you sure you want to REJECT this transaction?');">
                            Reject Transaction
                        </button>
                    </div>
                </form>

                @if($confirmation->expires_at)
                    <p class="text-center text-gray-500 text-sm mt-4">
                        This confirmation request expires at {{ $confirmation->expires_at->format('H:i:s') }}
                    </p>
                @endif
            </div>
        @elseif($confirmation->status === 'confirmed')
            <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-800 p-4 mb-4">
                <strong>Already Confirmed</strong><br>
                This transaction has already been confirmed.
            </div>
        @elseif($confirmation->status === 'rejected')
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <strong>Rejected</strong><br>
                This transaction was rejected during confirmation.
            </div>
        @endif

        <div class="text-center mt-4">
            <a href="/transactions/{{ $transaction->id }}" class="px-6 py-3 bg-blue-600 text-white no-underline rounded font-semibold hover:bg-blue-700 transition-colors inline-block">View Transaction Details</a>
        </div>
    </div>

    <footer class="text-center py-8 text-gray-500">
        <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
    </footer>
</body>
</html>
