@extends('layouts.app')

@section('title', 'Create Task - CEMS-MY')

@section('content')
<nav class="breadcrumb">
    <a href="{{ route('tasks.index') }}">Tasks</a>
    <span>/</span>
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
            <input type="text" id="title" name="title" class="form-input" required value="{{ old('title') }}">
            @error('title')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" class="form-input" required>
                    <option value="">Select Category</option>
                    @foreach(['Compliance', 'Customer', 'Operations', 'Admin', 'Approval'] as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="priority">Priority *</label>
                <select id="priority" name="priority" class="form-input" required>
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
                <select id="assigned_to" name="assigned_to" class="form-input">
                    <option value="">Select User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role->label() }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="due_at">Due Date</label>
                <input type="datetime-local" id="due_at" name="due_at" class="form-input" value="{{ old('due_at') }}">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-input" rows="4">{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-input" rows="2">{{ old('notes') }}</textarea>
        </div>

        @if($relatedCustomerId)
            <input type="hidden" name="related_customer_id" value="{{ $relatedCustomerId }}">
        @endif

        @if($relatedTransactionId)
            <input type="hidden" name="related_transaction_id" value="{{ $relatedTransactionId }}">
        @endif

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Create Task</button>
            <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
