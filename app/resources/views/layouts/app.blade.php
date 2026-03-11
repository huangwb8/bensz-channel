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
        <title>{{ $pageTitle ? $pageTitle.' · '.$siteName : $siteName }}</title>
        <meta name="description" content="{{ $siteTagline }}">
        <meta name="color-scheme" content="light dark">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        @include('partials.theme-bootstrap', [
            'themeMode' => $resolvedThemeMode,
            'themeDayStart' => $resolvedThemeDayStart,
            'themeNightStart' => $resolvedThemeNightStart,
        ])
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="theme-page-shell min-h-screen antialiased flex flex-col">
        @php
            $mobileCurrentChannelIcon = $currentChannel?->icon ?? '🏠';
            $mobileCurrentChannelName = $currentChannel?->name ?? '全部';
        @endphp

        <!-- 顶部固定导航栏 -->
        <header class="top-nav">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-3 px-4 sm:gap-4">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <button
                        type="button"
                        class="mobile-channel-trigger"
                        data-mobile-channel-trigger
                        aria-expanded="false"
                        aria-controls="mobile-channel-drawer"
                        title="打开频道列表"
                        aria-label="打开频道列表"
                    >
                        <span class="text-base" aria-hidden="true">☰</span>
                        <span class="flex min-w-0 items-center gap-2">
                            <span aria-hidden="true">{{ $mobileCurrentChannelIcon }}</span>
                            <span class="truncate">{{ $mobileCurrentChannelName }}</span>
                        </span>
                    </button>

                    <a href="{{ route('home') }}" class="logo flex shrink-0 items-center gap-2">
                        <span class="text-2xl">💬</span>
                        <span class="hidden sm:inline">{{ $siteName }}</span>
                    </a>
                </div>

                <!-- 中间版块标签 -->
                <nav class="channel-tabs flex-1 justify-center" aria-label="顶部频道导航">
                    <a
                        href="{{ route('home') }}"
                        class="channel-tab {{ $currentChannel === null ? 'channel-tab-active' : '' }}"
                        @if($currentChannel === null) aria-current="page" @endif
                    >
                        <span>🏠</span>
                        <span>全部</span>
                    </a>
                    @foreach($channels as $channel)
                        <a
                            href="{{ route('channels.show', $channel) }}"
                            class="channel-tab {{ optional($currentChannel)->is($channel) ? 'channel-tab-active' : '' }}"
                            @if(optional($currentChannel)->is($channel)) aria-current="page" @endif
                        >
                            <span>{{ $channel->icon }}</span>
                            <span>{{ $channel->name }}</span>
                        </a>
                    @endforeach
                </nav>

                <!-- 右侧用户操作 -->
                <div class="flex items-center gap-2">
                    @auth
                        <div class="relative" data-user-menu-shell>
                            <button
                                type="button"
                                class="flex min-h-11 items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                                data-user-menu-trigger
                                aria-haspopup="menu"
                                aria-expanded="false"
                                aria-controls="user-menu-panel"
                                title="打开用户菜单"
                            >
                                <span class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center text-xs font-semibold text-blue-700">
                                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                                </span>
                                <span class="hidden sm:inline max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <!-- 下拉菜单 -->
                            <div
                                id="user-menu-panel"
                                class="absolute right-0 top-full z-[70] mt-2 w-48 rounded-xl border border-gray-200 bg-white py-1 shadow-lg"
                                data-user-menu-panel
                                role="menu"
                                aria-hidden="true"
                                hidden
                            >
                                <div class="border-b border-gray-100 px-4 py-2">
                                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ auth()->user()->isAdmin() ? '管理员' : '成员' }}</p>
                                </div>
                                <a href="{{ route('settings.account.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                    👤 账户设置
                                </a>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.site-settings.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                        ⚙️ 站点设置
                                    </a>
                                    <a href="{{ route('admin.articles.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                        📝 管理文章
                                    </a>
                                    <a href="{{ route('admin.channels.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                        📁 管理频道
                                    </a>
                                    <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                        👥 管理用户
                                    </a>
                                @endif
                                <a href="{{ route('settings.subscriptions.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                    📮 订阅设置
                                </a>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.devtools.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                        🔧 DevTools
                                    </a>
                                @endif
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-50" role="menuitem">
                                        🚪 退出登录
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn-login">
                            登录 / 注册
                        </a>
                    @endauth
                </div>
            </div>

        </header>

        <div id="mobile-channel-drawer" class="mobile-channel-drawer-shell" data-mobile-channel-drawer hidden aria-hidden="true">
            <button
                type="button"
                class="mobile-channel-backdrop"
                data-mobile-channel-close
                title="关闭频道列表"
                aria-label="关闭频道列表"
            ></button>

            <div class="mobile-channel-drawer" role="dialog" aria-modal="true" aria-label="频道列表">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-4 py-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">切换频道</p>
                        <p class="mt-1 text-xs text-gray-500">移动端频道较多时，可在这里快速选择。</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:text-gray-700"
                        data-mobile-channel-close
                        data-mobile-channel-close-primary
                        title="关闭频道列表"
                        aria-label="关闭频道列表"
                    >
                        ✕
                    </button>
                </div>

                <nav class="mobile-channel-list" aria-label="移动端频道列表">
                    <a
                        href="{{ route('home') }}"
                        class="mobile-channel-link {{ $currentChannel === null ? 'mobile-channel-link-active' : '' }}"
                        data-mobile-channel-link
                        @if($currentChannel === null) aria-current="page" @endif
                    >
                        <span class="flex items-center gap-3">
                            <span class="text-lg">🏠</span>
                            <span>
                                <span class="block text-sm font-semibold">全部</span>
                                <span class="block text-xs text-gray-500">查看所有频道的最新内容</span>
                            </span>
                        </span>
                        <span class="text-sm text-gray-400">›</span>
                    </a>

                    @foreach($channels as $channel)
                        <a
                            href="{{ route('channels.show', $channel) }}"
                            class="mobile-channel-link {{ optional($currentChannel)->is($channel) ? 'mobile-channel-link-active' : '' }}"
                            data-mobile-channel-link
                            @if(optional($currentChannel)->is($channel)) aria-current="page" @endif
                        >
                            <span class="flex items-center gap-3">
                                <span class="text-lg">{{ $channel->icon }}</span>
                                <span>
                                    <span class="block text-sm font-semibold">{{ $channel->name }}</span>
                                    <span class="block text-xs text-gray-500">{{ $channel->description ?: '进入 '.$channel->name.' 频道' }}</span>
                                </span>
                            </span>
                            <span class="text-sm text-gray-400">›</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>

        <!-- 主内容区 -->
        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
            @if(($staticPage ?? false) === true)
                <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm text-blue-700">
                    📦 静态快照模式
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('otp_preview'))
                <div class="mb-4 alert-warning">
                    开发环境验证码预览：<span class="font-mono font-semibold tracking-widest">{{ session('otp_preview') }}</span>
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-medium">请先修正以下问题：</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- 页脚 -->
        <footer class="border-t border-gray-200 bg-white py-6 mt-8">
            <div class="mx-auto max-w-6xl px-4 text-center text-sm text-gray-500">
                <p>{{ $siteName }} · {{ $siteTagline }}</p>
            </div>
        </footer>
    </body>
</html>
