@extends('layouts.app')

@section('title', 'Verify MFA - CEMS-MY')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-shield-check"></i>
                        Verify Your Identity
                    </h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-key" style="font-size: 3rem; color: #0d6efd;"></i>
                        <p class="mt-2 text-muted">
                            Enter the 6-digit code from your authenticator app
                        </p>
                    </div>

                    <form method="POST" action="{{ route('mfa.verify.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="code" class="form-label">Verification Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" placeholder="000000"
                                   maxlength="6" pattern="\d{6}" required
                                   autocomplete="one-time-code" autofocus>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($rememberDevice)
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_device"
                                   name="remember_device" value="1" checked>
                            <label class="form-check-label" for="remember_device">
                                Remember this device for {{ config('cems.mfa.remember_days', 30) }} days
                            </label>
                            <div class="form-text">
                                Skip MFA verification on this device for a month.
                            </div>
                        </div>
                        @endif

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Verify
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <a href="{{ route('mfa.recovery') }}" class="text-decoration-none">
                            <i class="bi bi-question-circle"></i> Use a recovery code instead
                        </a>
                    </div>
                </div>
                <div class="card-footer text-center text-muted small">
                    <p class="mb-0">Having trouble? Contact your system administrator.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('code').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
});
</script>
@endpush
@endsection
