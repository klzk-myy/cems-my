@extends('layouts.app')

@section('title', 'MFA Recovery - CEMS-MY')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-life-preserver"></i>
                        Use Recovery Code
                    </h4>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center">
                        Enter one of your recovery codes to access your account.
                        Each code can only be used once.
                    </p>

                    <form method="POST" action="{{ route('mfa.verify.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="code" class="form-label">Recovery Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" placeholder="XXXX-XXXX"
                                   style="text-transform: uppercase;" required autofocus>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Verify
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <a href="{{ route('mfa.verify') }}" class="text-decoration-none">
                            <i class="bi bi-qr-code-scan"></i> Use authenticator code instead
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('code').addEventListener('input', function(e) {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 9);
});
</script>
@endpush
@endsection
