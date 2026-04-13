@extends('layouts.base')

@section('title', 'Edit Customer')

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Edit Customer</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('customers.update', $customer->id ?? 0) }}">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" value="{{ $customer->name ?? '' }}" required>
                </div>
                <div>
                    <label class="form-label">IC Number / Passport</label>
                    <input type="text" name="ic_number" class="form-input" value="{{ $customer->ic_number ?? '' }}" required>
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="{{ $customer->email ?? '' }}">
                </div>
                <div>
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input" value="{{ $customer->phone ?? '' }}">
                </div>
                <div>
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-input" rows="2">{{ $customer->address ?? '' }}</textarea>
                </div>
                <div>
                    <label class="form-label">Risk Level</label>
                    <select name="risk_level" class="form-input">
                        <option value="low" @if(($customer->risk_level ?? '') === 'low') selected @endif>Low</option>
                        <option value="medium" @if(($customer->risk_level ?? '') === 'medium') selected @endif>Medium</option>
                        <option value="high" @if(($customer->risk_level ?? '') === 'high') selected @endif>High</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn btn-primary">Update Customer</button>
                <a href="{{ route('customers.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection