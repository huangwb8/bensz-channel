<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ isset($pageTitle) && $pageTitle ? $pageTitle . ' · ' . ($siteName ?? 'Bensz Channel') : ($siteName ?? 'Bensz Channel') }}</title>
        <meta name="description" content="{{ $siteTagline ?? '' }}">
        <meta name="color-scheme" content="light dark">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @stack('styles')
    </head>
    <body
        class="antialiased"
        data-theme-mode="{{ $themeMode ?? 'auto' }}"
        data-theme-day-start="{{ $themeDayStart ?? '07:00' }}"
        data-theme-night-start="{{ $themeNightStart ?? '19:00' }}"
        data-theme="{{ $themeApplied ?? 'light' }}"
    >
        @yield('content')
        @stack('scripts')
    </body>
</html>
