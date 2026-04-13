@extends('layouts.base')

@section('title', 'Create AML Rule')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Create AML Rule</h3></div>
        <div class="card-body">
            <form method="POST" action="/compliance/rules">
                @csrf
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        @foreach($ruleTypeOptions ?? [] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Conditions (JSON)</label>
                    <textarea name="conditions" class="form-textarea font-mono" rows="4" placeholder='{"field": "value"}'></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <a href="/compliance/rules" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
