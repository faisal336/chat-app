<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0a0d14">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">

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
    <div class="min-h-full">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
