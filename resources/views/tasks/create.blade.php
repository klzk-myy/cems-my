@extends('layouts.app')

@section('title', 'Create Task - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('tasks.index') }}">Tasks</a>
    <span>›</span>
    <span>Create</span>
</nav>

<div class="page-header">
    <h1>Create New Task</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('tasks.store') }}">
        @csrf

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" class="form-control" required value="{{ old('title') }}">
            @error('title')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" class="form-control" required>
                    <option value="">Select Category</option>
                    @foreach(['Compliance', 'Customer', 'Operations', 'Admin', 'Approval'] as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="priority">Priority *</label>
                <select id="priority" name="priority" class="form-control" required>
                    <option value="">Select Priority</option>
                    @foreach(['Urgent', 'High', 'Medium', 'Low'] as $pri)
                        <option value="{{ $pri }}" {{ old('priority') === $pri ? 'selected' : '' }}>{{ $pri }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="assigned_to">Assign To</label>
                <select id="assigned_to" name="assigned_to" class="form-control">
                    <option value="">Select User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role->label() }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="due_at">Due Date</label>
                <input type="datetime-local" id="due_at" name="due_at" class="form-control" value="{{ old('due_at') }}">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>

        @if($relatedCustomerId)
            <input type="hidden" name="related_customer_id" value="{{ $relatedCustomerId }}">
        @endif

        @if($relatedTransactionId)
            <input type="hidden" name="related_transaction_id" value="{{ $relatedTransactionId }}">
        @endif

        <div class="button-group">
            <button type="submit" class="btn btn-primary">Create Task</button>
            <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

@section('styles')
<style>
.form-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    max-width: 800px;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #4a5568;
}

.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 0.875rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.error {
    color: #e53e3e;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.button-group {
    display: flex;
    gap: 0.5rem;
    margin-top: 1.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: #3182ce;
    color: white;
}

.btn-secondary {
    background: #e2e8f0;
    color: #4a5568;
}
</style>
@endsection