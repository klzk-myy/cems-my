@php
    $attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag([]);
@endphp

<x-app-layout title="New Transaction" {{ $attributes }}>
    <x-page-header title="New Transaction" description="Record a new buy or sell transaction." />

    <form method="POST" action="{{ route('transactions.store') }}">
        @csrf
        @php
            // Reuse one idempotency key across validation redirects so a retry
            // after a validation error does not create a duplicate booking.
            $idempotencyKey = session('tx_idempotency_key') ?? \Illuminate\Support\Str::uuid()->toString();
            session(['tx_idempotency_key' => $idempotencyKey]);
        @endphp
        <input type="hidden" name="branch_id" value="{{ auth()->user()?->branch_id }}">
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-select name="type" label="Transaction Type" :options="['Buy' => 'Buy', 'Sell' => 'Sell']" required />

            <x-select name="customer_id" label="Customer" :options="$customers ?? []" required />

            <x-select name="currency_code" label="Currency" :options="$currencies ?? []" required />

            <x-input type="number" name="amount_foreign" label="Foreign Amount" step="0.01" required />

            <x-input type="number" name="rate" label="Exchange Rate" step="0.0001" required />

            <x-select name="counter_id" label="Counter" :options="$counters ?? []" required />

            <x-select name="purpose" label="Purpose" :options="['Travel' => 'Travel', 'Education' => 'Education', 'Medical' => 'Medical', 'Business' => 'Business', 'Investment' => 'Investment', 'Family Support' => 'Family Support', 'Migration' => 'Migration', 'Other' => 'Other']" required />

            <x-input name="source_of_funds" label="Source of Funds" placeholder="e.g. Salary, Savings, Business Income" required />
        </div>

        <div class="mt-6 flex gap-2">
            <x-button type="submit" variant="primary">Create Transaction</x-button>
            <x-button href="{{ route('transactions.index') }}" variant="secondary">Cancel</x-button>
        </div>
    </form>
</x-app-layout>