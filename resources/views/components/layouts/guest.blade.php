<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0a0d14">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16.png">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full antialiased">
    <div class="relative min-h-full overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-brand-500/20 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-32 h-[28rem] w-[28rem] rounded-full bg-violet-500/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-surface-950/40 dark:to-black/60"></div>
        </div>

        <main class="flex min-h-svh items-center justify-center px-4 py-12">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
