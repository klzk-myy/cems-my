<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas-subtle text-ink flex items-center justify-center">
    <div class="w-full max-w-md p-6">
        <x-card>
            <div class="p-8 space-y-6">
                <x-page-header title="Forgot Password" class="justify-center" />

                <p class="text-sm text-ink-muted">
                    Enter your account email address and we will send you a password reset link.
                </p>

                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf

                    @if(session('status'))
                        <x-alert type="success">{{ session('status') }}</x-alert>
                    @endif

                    @if($errors->any())
                        <x-alert type="error">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <x-input type="email" name="email" label="Email Address" required value="{{ old('email') }}" inline />

                    <x-button type="submit" variant="primary" class="w-full">Send Reset Link</x-button>
                </form>

                <a href="{{ route('login') }}" class="block text-sm text-info-text hover:text-info text-center">
                    Back to Sign In
                </a>
            </div>
        </x-card>
    </div>
</body>
</html>
