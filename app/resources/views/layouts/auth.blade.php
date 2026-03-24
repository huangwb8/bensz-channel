@php
    $resolvedThemeMode = strtolower(trim((string) ($themeMode ?? 'auto')));
    $resolvedThemeMode = in_array($resolvedThemeMode, ['light', 'dark', 'auto'], true) ? $resolvedThemeMode : 'auto';
    $resolvedThemeDayStart = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) ($themeDayStart ?? '')) === 1
        ? (string) $themeDayStart
        : '07:00';
    $resolvedThemeNightStart = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) ($themeNightStart ?? '')) === 1
        ? (string) $themeNightStart
        : '19:00';
    $resolvedThemeApplied = in_array($resolvedThemeMode, ['light', 'dark'], true)
        ? $resolvedThemeMode
        : 'light';
@endphp
<!DOCTYPE html>
<html
    lang="zh-CN"
    data-theme-mode="{{ $resolvedThemeMode }}"
    data-theme-day-start="{{ $resolvedThemeDayStart }}"
    data-theme-night-start="{{ $resolvedThemeNightStart }}"
    data-theme="{{ $resolvedThemeApplied }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($pageTitle) && $pageTitle ? $pageTitle . ' · ' . ($siteName ?? 'Bensz Channel') : ($siteName ?? 'Bensz Channel') }}</title>
        @include('partials.seo-meta')
        <meta name="color-scheme" content="light dark">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
        @include('partials.theme-bootstrap', [
            'themeMode' => $resolvedThemeMode,
            'themeDayStart' => $resolvedThemeDayStart,
            'themeNightStart' => $resolvedThemeNightStart,
        ])
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @stack('styles')
    </head>
    <body class="antialiased">
        @yield('content')
        @include('partials.site-footer')
        @stack('scripts')
    </body>
</html>
