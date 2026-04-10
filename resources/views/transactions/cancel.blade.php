<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Transaction #{{ $transaction->id }} - CEMS-MY</title>
</head>
<body class="font-sans bg-gray-100 text-gray-800 m-0 p-0">
    <header class="bg-blue-900 text-white px-8 py-4 flex justify-between items-center">
        <h1 class="text-xl font-semibold">CEMS-MY Cancel Transaction</h1>
        <nav class="flex gap-4">
            <a href="/" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Dashboard</a>
            <a href="/transactions" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Transactions</a>
            <a href="/stock-cash" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Stock/Cash</a>
            <a href="/compliance" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Compliance</a>
            <a href="/accounting" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Accounting</a>
            <a href="/reports" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Reports</a>
            <a href="/users" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Users</a>
            <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-white no-underline px-3 py-2 rounded hover:bg-white/10 transition-colors">Logout</a>
        </nav>
        <form id="logout-form" action="/logout" method="POST" class="hidden">
            @csrf
        </form>
    </header>

    <div class="max-w-3xl mx-auto my-8 px-4">
        <!-- Warning Header -->
        <div class="bg-red-50 border-2 border-red-400 rounded-lg p-6 mb-6 text-center">
            <h2 class="text-2xl font-bold text-red-700 mb-2">⚠️ Cancel Transaction</h2>
            <p class="text-red-800">You are about to cancel Transaction #{{ $transaction->id }}</p>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <strong>Please correct the following errors:</strong>
                <ul class="mt-2 ml-6 list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Transaction Summary -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-blue-900 mb-4 border-b-2 border-gray-200 pb-2">Transaction Summary</h2>
            <div class="divide-y divide-gray-200">
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Transaction ID</span>
                    <span class="text-gray-800 font-semibold">#{{ $transaction->id }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Customer</span>
                    <span class="text-gray-800 font-semibold">
                        {{ str_repeat('*', strlen($transaction->customer->full_name ?? '') - 3) . substr($transaction->customer->full_name ?? 'N/A', -3) }}
                    </span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Type</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->type }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Amount (Foreign)</span>
                    <span class="text-gray-800 font-semibold">{{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Rate</span>
                    <span class="text-gray-800 font-semibold">{{ number_format($transaction->rate, 6) }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Amount (MYR)</span>
                    <span class="text-gray-800 font-semibold">RM {{ number_format($transaction->amount_local, 2) }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500 font-medium">Created</span>
                    <span class="text-gray-800 font-semibold">{{ $transaction->created_at->format('Y-m-d H:i:s') }}</span>
                </div>
            </div>
        </div>

        <!-- Consequences Warning -->
        <div class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-6">
            <h3 class="text-orange-700 font-semibold mb-2">⚠️ This action will:</h3>
            <ul class="text-orange-800 ml-6 list-disc space-y-1">
                <li>Create a refund transaction to reverse this transaction</li>
                <li>Reverse the stock position for {{ $transaction->currency_code }}</li>
                <li>Create reversing accounting journal entries</li>
                <li><strong>This action cannot be undone</strong></li>
            </ul>
        </div>

        <!-- Cancellation Form -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-blue-900 mb-4 border-b-2 border-gray-200 pb-2">Cancellation Reason</h2>
            <form action="{{ route('transactions.cancel', $transaction) }}" method="POST">
                @csrf

                <div class="mb-6">
                    <label for="cancellation_reason" class="block mb-2 font-semibold text-gray-800">
                        Reason for Cancellation <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="cancellation_reason"
                        id="cancellation_reason"
                        class="w-full p-3 border border-gray-200 rounded text-sm font-sans resize-y min-h-32"
                        placeholder="Please provide a detailed reason for cancelling this transaction (minimum 10 characters)..."
                        required
                        minlength="10"
                        maxlength="1000"
                    >{{ old('cancellation_reason') }}</textarea>
                    @error('cancellation_reason')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-6">
                    <div class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            name="confirm_understanding"
                            id="confirm_understanding"
                            value="1"
                            {{ old('confirm_understanding') ? 'checked' : '' }}
                            required
                            class="mt-1 w-5 h-5 cursor-pointer"
                        >
                        <label for="confirm_understanding" class="cursor-pointer font-normal text-gray-700">
                            I understand that this action <strong>cannot be undone</strong> and will create a refund transaction, reverse stock movements, and create reversing accounting entries.
                        </label>
                    </div>
                    @error('confirm_understanding')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex gap-4 justify-center mt-8">
                    <a href="{{ route('transactions.show', $transaction) }}" class="px-6 py-3 bg-gray-200 text-gray-700 no-underline rounded font-semibold hover:bg-gray-300 transition-colors">Back to Transaction</a>
                    <button type="submit" class="px-6 py-3 bg-red-600 text-white no-underline rounded font-semibold hover:bg-red-700 transition-colors cursor-pointer border-0">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
