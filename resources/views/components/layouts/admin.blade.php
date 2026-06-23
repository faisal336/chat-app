<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a0d14">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin · '.config('app.name') }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.ico">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full antialiased">
<div class="min-h-svh flex bg-surface-50 dark:bg-surface-950">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex w-64 flex-shrink-0 flex-col border-r border-slate-200 dark:border-white/10 bg-white dark:bg-surface-900/40">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-900 dark:text-white text-sm">Admin</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ config('app.name') }}</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 p-3 space-y-1">
            @php
                $items = [
                    ['admin.dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['admin.users', 'Users', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                    ['admin.deleted-messages', 'Deleted messages', 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
                    ['admin.audit-logs', 'Audit logs', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['admin.pin-resets', 'PIN reset requests', 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
                ];
            @endphp
            @foreach($items as [$route, $label, $icon])
                <a href="{{ route($route) }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition
                          {{ request()->routeIs($route) || ($route === 'admin.dashboard' && request()->routeIs('admin.dashboard'))
                              ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium'
                              : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                    </svg>
                    <span class="truncate">{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        <div class="p-3 border-t border-slate-200 dark:border-white/10 space-y-1">
            <a href="{{ route('chat.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Back to chat
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="h-16 flex-shrink-0 px-6 border-b border-slate-200 dark:border-white/10 bg-white/60 dark:bg-surface-900/60 backdrop-blur flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title ?? 'Admin' }}</h1>
            <button type="button"
                    onclick="window.dispatchEvent(new CustomEvent('chatapp:set-theme', { detail: { theme: document.documentElement.classList.contains('dark') ? 'light' : 'dark' } }))"
                    class="btn btn-ghost p-2">
                <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto scrollbar-thin p-6">
            {{ $slot }}
        </div>
    </main>
</div>
@livewireScripts
</body>
</html>
