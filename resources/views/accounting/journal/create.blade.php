@extends('layouts.app')

@section('title', 'Create Journal Entry - CEMS-MY')

@section('content')
<div class="journal-header">
    <h2>Create Journal Entry</h2>
    <div class="header-actions">
        <a href="{{ route('accounting.journal') }}" class="btn btn-secondary">Back to Journal</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="{{ route('accounting.journal.store') }}">
        @csrf
        
        <div class="form-row">
            <div class="form-group">
                <label for="entry_date">Entry Date</label>
                <input type="date" id="entry_date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" class="form-control" required>
                @error('entry_date')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" value="{{ old('description') }}" class="form-control" required maxlength="500">
                @error('description')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>
        </div>
        
        <h3>Journal Lines</h3>
        <p style="color: #718096; margin-bottom: 1rem;">Enter at least 2 lines. Total debits must equal total credits.</p>
        
        @error('lines')
            <div class="alert alert-error">{{ $message }}</div>
        @enderror
        
        <div id="journal-lines">
            @for($i = 0; $i < 2; $i++)
            <div class="journal-line-row">
                <div class="line-number">{{ $i + 1 }}</div>
                <div class="form-group">
                    <label>Account</label>
                    <select name="lines[{{ $i }}][account_code]" class="form-control" required>
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->account_code }}" {{ old("lines.{$i}.account_code") == $account->account_code ? 'selected' : '' }}>
                                {{ $account->account_code }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Debit (MYR)</label>
                    <input type="number" name="lines[{{ $i }}][debit]" value="{{ old("lines.{$i}.debit", 0) }}" class="form-control" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Credit (MYR)</label>
                    <input type="number" name="lines[{{ $i }}][credit]" value="{{ old("lines.{$i}.credit", 0) }}" class="form-control" step="0.01" min="0">
                </div>
                <div class="form-group" style="flex: 2;">
                    <label>Description</label>
                    <input type="text" name="lines[{{ $i }}][description]" value="{{ old("lines.{$i}.description") }}" class="form-control" maxlength="255">
                </div>
            </div>
            @endfor
        </div>
        
        <button type="button" id="add-line" class="btn btn-secondary" style="margin-bottom: 1.5rem;">+ Add Line</button>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Journal Entry</button>
        </div>
    </form>
</div>

@section('styles')
<style>
    .journal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .header-actions {
        display: flex;
        gap: 0.5rem;
    }
    .journal-line-row {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    .line-number {
        width: 30px;
        height: 30px;
        background: #3182ce;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .form-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .form-group {
        flex: 1;
        min-width: 150px;
    }
    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
    }
    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
    }
    .alert-error {
        background: #fed7d7;
        color: #c53030;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .error {
        color: #c53030;
        font-size: 0.75rem;
    }
</style>
@endsection

@section('scripts')
<script>
    let lineCount = 2;
    document.getElementById('add-line').addEventListener('click', function() {
        const container = document.getElementById('journal-lines');
        const newRow = document.createElement('div');
        newRow.className = 'journal-line-row';
        newRow.innerHTML = `
            <div class="line-number">${lineCount + 1}</div>
            <div class="form-group">
                <label>Account</label>
                <select name="lines[${lineCount}][account_code]" class="form-control" required>
                    <option value="">Select Account</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->account_code }}">
                            {{ $account->account_code }} - {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Debit (MYR)</label>
                <input type="number" name="lines[${lineCount}][debit]" value="0" class="form-control" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label>Credit (MYR)</label>
                <input type="number" name="lines[${lineCount}][credit]" value="0" class="form-control" step="0.01" min="0">
            </div>
            <div class="form-group" style="flex: 2;">
                <label>Description</label>
                <input type="text" name="lines[${lineCount}][description]" value="" class="form-control" maxlength="255">
            </div>
        `;
        container.appendChild(newRow);
        lineCount++;
    });
</script>
@endsection
@endsection
