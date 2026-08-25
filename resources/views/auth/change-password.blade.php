<x-app-layout title="Change Password">
    <x-page-header title="Change Password" description="Your password has expired under the security rotation policy. Choose a new one to continue." />

    @if (session('warning'))
        <div class="mb-4 rounded border border-amber-300 bg-amber-50 p-3 text-amber-800">{{ session('warning') }}</div>
    @endif

    <form method="POST" action="{{ route('password.change') }}" class="max-w-md space-y-4">
        @csrf

        <x-input type="password" name="current_password" label="Current Password" required />

        <x-input type="password" name="password" label="New Password" required minlength="12" />

        <x-input type="password" name="password_confirmation" label="Confirm New Password" required minlength="12" />

        <p class="text-sm text-gray-500">Minimum 12 characters with upper case, lower case, a digit and a symbol.</p>

        <x-button type="submit" variant="primary">Update Password</x-button>
    </form>
</x-app-layout>
