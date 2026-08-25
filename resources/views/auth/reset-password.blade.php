<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas-subtle text-ink flex items-center justify-center">
    <div class="w-full max-w-md p-6">
        <x-card>
            <div class="p-8 space-y-6">
                <x-page-header title="Reset Password" class="justify-center" />

                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf

                    @if($errors->any())
                        <x-alert type="error">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <input type="hidden" name="token" value="{{ $token }}">

                    <x-input type="email" name="email" label="Email Address" required value="{{ old('email', $email ?? '') }}" inline />
                    <x-input type="password" name="password" label="New Password" required inline />
                    <x-input type="password" name="password_confirmation" label="Confirm New Password" required inline />

                    <x-button type="submit" variant="primary" class="w-full">Reset Password</x-button>
                </form>

                <a href="{{ route('login') }}" class="block text-sm text-info-text hover:text-info text-center">
                    Back to Sign In
                </a>
            </div>
        </x-card>
    </div>
</body>
</html>
