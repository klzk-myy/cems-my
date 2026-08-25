<!-- resources/views/components/app-layout.blade.php -->
@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {{ $attributes }}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' - ' . config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas-subtle text-ink text-sm">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-on-primary focus:rounded-lg">
        Skip to main content
    </a>
    <div id="app" class="flex">
        @auth
            <x-navigation />
            <div class="flex-1 flex flex-col min-w-0 min-h-screen">
                {{-- Top header: notification bell + DLQ badge for admins (Section 3.10) --}}
                <header class="h-14 bg-surface border-b border-border px-6 flex items-center justify-end gap-3 shrink-0">
                    <x-notification-bell
                        :unread-notifications="$unreadNotifications"
                        :unread-count="$unreadNotificationCount"
                        :dlq-count="$headerDlqCount"
                    />
                </header>

                <main id="main-content" class="flex-1 max-w-7xl mx-auto px-6 py-6 w-full">
                    {{-- Toast container (Section 3.10) --}}
                    <div x-data="{ toasts: [] }"
                         x-init="window.addEventListener('showToast', (e) => {
                                toasts.push({ message: e.detail.message, type: e.detail.type || 'success' });
                                setTimeout(() => toasts.shift(), 4000);
                              });"
                         class="relative z-50">
                        <template x-for="toast in toasts" :key="toast">
                            <div x-show="toast"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-x-8"
                                 x-transition:enter-end="opacity-100 translate-x-0"
                                 :class="['fixed top-4 right-4 flex items-center gap-2 px-4 py-3 rounded-lg shadow-lg text-sm text-on-primary',
                                          toast.type === 'error' ? 'bg-danger' :
                                          toast.type === 'warning' ? 'bg-warning' : 'bg-success']">
                                <span x-text="toast.message"></span>
                            </div>
                        </template>
                    </div>

                    {{ $slot }}
                </main>

                {{-- Footer (C189-C192) --}}
                <footer class="bg-surface-inverted text-ink-muted px-6 py-3 flex items-center justify-between">
                    <span class="text-xs">{{ $currentUser?->branch?->name ?? config('app.name') }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-primary-hover flex items-center justify-center">
                            <span class="text-xs font-medium text-sidebar-text">{{ strtoupper(substr($userName ?? 'U', 0, 1)) }}</span>
                        </div>
                        <span class="text-xs">{{ $userName }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline ml-2">
                            @csrf
                            <button type="submit" class="text-xs text-ink-muted hover:text-ink underline">Logout</button>
                        </form>
                    </div>
                </footer>
            </div>
        @else
            <main id="main-content" class="flex-1">
                {{ $slot }}
            </main>
        @endauth
    </div>
</body>
</html>
