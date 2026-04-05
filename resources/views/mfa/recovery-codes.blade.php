@extends('layouts.app')

@section('title', 'Recovery Codes - CEMS-MY')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        Save Your Recovery Codes
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <strong>Important!</strong> These recovery codes are shown only ONCE.
                        Please save them in a secure location immediately.
                    </div>

                    <p>
                        If you lose access to your authenticator app, you can use one of these
                        recovery codes to sign in. Each code can only be used once.
                    </p>

                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="bg-light p-4 rounded border">
                                <div class="row">
                                    @foreach($recoveryCodes as $index => $code)
                                        <div class="col-6 mb-2">
                                            <code class="font-monospace" style="font-size: 1.1rem;">
                                                {{ $code }}
                                            </code>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('mfa.trusted-devices') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-phone"></i> Manage Trusted Devices
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> I've Saved My Codes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
