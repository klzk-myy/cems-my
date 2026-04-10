@extends('layouts.app')

@section('title', 'Create Journal Entry - CEMS-MY')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Create Journal Entry</h2>
    <a href="{{ route('accounting.journal') }}" class="px-4 py-2 bg-gray-200 text-gray-700 no-underline rounded font-semibold text-sm hover:bg-gray-300 transition-colors">Back to Journal</a>
</div>

<div class="bg-white rounded-lg shadow-sm p-6">
    <form method="POST" action="{{ route('accounting.journal.store') }}">
        @csrf

        <div class="mb-4">
            <label for="entry_date" class="block mb-1 text-sm font-semibold text-gray-700">Entry Date</label>
            <input type="date" id="entry_date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required class="w-full p-2 border border-gray-200 rounded text-sm">
            @error('entry_date')
                <span class="text-red-600 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-6">
            <label for="description" class="block mb-1 text-sm font-semibold text-gray-700">Description</label>
            <input type="text" id="description" name="description" value="{{ old('description') }}" required maxlength="500" class="w-full p-2 border border-gray-200 rounded text-sm">
            @error('description')
                <span class="text-red-600 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-2">Journal Lines</h3>
        <p class="text-gray-500 text-sm mb-4">Enter at least 2 lines. Total debits must equal total credits.</p>

        @error('lines')
            <div class="p-3 mb-4 rounded bg-red-100 text-red-800">{{ $message }}</div>
        @enderror

        <div id="journal-lines">
            @for($i = 0; $i < 2; $i++)
            <div class="flex gap-4 items-end p-4 bg-gray-50 rounded-lg mb-4">
                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold text-sm flex-shrink-0">{{ $i + 1 }}</div>
                <div class="flex-1 min-w-0">
                    <label class="block mb-1 text-xs font-medium text-gray-600">Account</label>
                    <select name="lines[{{ $i }}][account_code]" required class="w-full p-2 border border-gray-200 rounded text-sm bg-white">
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->account_code }}" {{ old("lines.{$i}.account_code") == $account->account_code ? 'selected' : '' }}>
                                {{ $account->account_code }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-32">
                    <label class="block mb-1 text-xs font-medium text-gray-600">Debit (MYR)</label>
                    <input type="number" name="lines[{{ $i }}][debit]" value="{{ old("lines.{$i}.debit", 0) }}" step="0.01" min="0" class="w-full p-2 border border-gray-200 rounded text-sm">
                </div>
                <div class="w-32">
                    <label class="block mb-1 text-xs font-medium text-gray-600">Credit (MYR)</label>
                    <input type="number" name="lines[{{ $i }}][credit]" value="{{ old("lines.{$i}.credit", 0) }}" step="0.01" min="0" class="w-full p-2 border border-gray-200 rounded text-sm">
                </div>
                <div class="flex-1 min-w-0">
                    <label class="block mb-1 text-xs font-medium text-gray-600">Description</label>
                    <input type="text" name="lines[{{ $i }}][description]" value="{{ old("lines.{$i}.description") }}" maxlength="255" class="w-full p-2 border border-gray-200 rounded text-sm">
                </div>
            </div>
            @endfor
        </div>

        <button type="button" id="add-line" class="px-4 py-2 bg-gray-200 text-gray-700 rounded font-semibold text-sm hover:bg-gray-300 transition-colors mb-6">+ Add Line</button>

        <div class="flex gap-4 pt-6 mt-6 border-t border-gray-200">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-semibold hover:bg-blue-700 transition-colors">Create Journal Entry</button>
        </div>
    </form>
</div>

@section('scripts')
<script>
    let lineCount = 2;
    document.getElementById('add-line').addEventListener('click', function() {
        const container = document.getElementById('journal-lines');
        const newRow = document.createElement('div');
        newRow.className = 'flex gap-4 items-end p-4 bg-gray-50 rounded-lg mb-4';
        newRow.innerHTML = `
            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold text-sm flex-shrink-0">${lineCount + 1}</div>
            <div class="flex-1 min-w-0">
                <label class="block mb-1 text-xs font-medium text-gray-600">Account</label>
                <select name="lines[${lineCount}][account_code]" required class="w-full p-2 border border-gray-200 rounded text-sm bg-white">
                    <option value="">Select Account</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->account_code }}">
                            {{ $account->account_code }} - {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label class="block mb-1 text-xs font-medium text-gray-600">Debit (MYR)</label>
                <input type="number" name="lines[${lineCount}][debit]" value="0" step="0.01" min="0" class="w-full p-2 border border-gray-200 rounded text-sm">
            </div>
            <div class="w-32">
                <label class="block mb-1 text-xs font-medium text-gray-600">Credit (MYR)</label>
                <input type="number" name="lines[${lineCount}][credit]" value="0" step="0.01" min="0" class="w-full p-2 border border-gray-200 rounded text-sm">
            </div>
            <div class="flex-1 min-w-0">
                <label class="block mb-1 text-xs font-medium text-gray-600">Description</label>
                <input type="text" name="lines[${lineCount}][description]" value="" maxlength="255" class="w-full p-2 border border-gray-200 rounded text-sm">
            </div>
        `;
        container.appendChild(newRow);
        lineCount++;
    });
</script>
@endsection
@endsection
