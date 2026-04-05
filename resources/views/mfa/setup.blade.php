@extends('layouts.app')

@section('title', 'Set Up MFA - CEMS-MY')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-shield-lock"></i>
                        Set Up Multi-Factor Authentication
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>BNM Compliance:</strong> Multi-factor authentication is required for your role.
                        Please set up an authenticator app to continue.
                    </div>

                    <div class="row">
                        <div class="col-md-6 text-center">
                            <h5>Scan QR Code</h5>
                            <p class="text-muted small">Use Google Authenticator, Authy, or any TOTP app</p>

                            <div class="qr-code-container p-3 bg-white rounded">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($otpauthUrl) }}"
                                     alt="QR Code" class="img-fluid" style="max-width: 200px;">
                            </div>

                            <div class="mt-3">
                                <small class="text-muted">Cannot scan? Enter this key manually:</small>
                                <div class="input-group mt-2">
                                    <input type="text" class="form-control font-monospace text-center"
                                           value="{{ $secret }}" readonly id="secret-key"
                                           style="font-size: 0.85rem; letter-spacing: 1px;">
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="copySecret()" title="Copy to clipboard">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5>Verification</h5>
                            <p class="text-muted small">
                                Enter the 6-digit code from your authenticator app to verify setup.
                            </p>

                            <form method="POST" action="{{ route('mfa.setup.store') }}">
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

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Verify and Enable MFA
                                    </button>
                                </div>
                            </form>

                            <hr class="my-4">

                            <h6>Instructions</h6>
                            <ol class="small text-muted">
                                <li>Download Google Authenticator or Authy from your app store</li>
                                <li>Scan the QR code or enter the key manually</li>
                                <li>Enter the 6-digit code shown in your app</li>
                                <li>Save your recovery codes in a secure location</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copySecret() {
    const secretInput = document.getElementById('secret-key');
    navigator.clipboard.writeText(secretInput.value).then(() => {
        alert('Secret key copied to clipboard!');
    });
}

// Auto-format code input
document.getElementById('code').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
});
</script>
@endpush
@endsection
