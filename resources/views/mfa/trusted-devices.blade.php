@extends('layouts.app')

@section('title', 'Trusted Devices - CEMS-MY')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-phone"></i>
                        Trusted Devices
                    </h4>
                </div>
                <div class="card-body">
                    @if(session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <p class="text-muted">
                        Trusted devices allow you to skip MFA verification for
                        {{ config('cems.mfa.remember_days', 30) }} days after your last use.
                    </p>

                    @if($devices->isEmpty())
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            No trusted devices found. When you verify with "Remember this device"
                            checked, your device will appear here.
                        </div>
                    @else
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Last Used</th>
                                    <th>Expires</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($devices as $device)
                                    <tr>
                                        <td>
                                            <i class="bi bi-device-desktop"></i>
                                            {{ $device->device_name ?? 'Unknown Device' }}
                                            <br>
                                            <small class="text-muted">{{ $device->ip_address }}</small>
                                        </td>
                                        <td>
                                            {{ $device->last_used_at?->diffForHumans() ?? 'Never' }}
                                        </td>
                                        <td>
                                            @if($device->expires_at)
                                                {{ $device->expires_at->diffForHumans() }}
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST"
                                                  action="{{ route('mfa.trusted-devices.remove', $device->id) }}"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Remove this trusted device?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                        <form method="POST" action="{{ route('mfa.disable') }}" class="d-inline">
                            @csrf
                            <div class="input-group">
                                <input type="text" name="code" class="form-control"
                                       placeholder="Code to disable" required>
                                <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('Disable MFA? You will need to set it up again.')">
                                    <i class="bi bi-slash-circle"></i> Disable MFA
                                </button>
                            </div>
                            @error('code')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
