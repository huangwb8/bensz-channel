<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle ? $pageTitle.' · '.$siteName : $siteName }}</title>
        <meta name="description" content="{{ $siteTagline }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="antialiased">
        <!-- 顶部固定导航栏 -->
        <header class="top-nav">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4">
                <!-- 左侧 Logo -->
                <a href="{{ route('home') }}" class="logo flex items-center gap-2">
                    <span class="text-2xl">💬</span>
                    <span class="hidden sm:inline">{{ $siteName }}</span>
                </a>

                <!-- 中间版块标签 -->
                <nav class="channel-tabs flex-1 justify-center">
                    <a href="{{ route('home') }}" class="channel-tab {{ $currentChannel === null ? 'channel-tab-active' : '' }}">
                        <span>🏠</span>
                        <span>全部</span>
                    </a>
                    @foreach($channels as $channel)
                        <a href="{{ route('channels.show', $channel) }}" class="channel-tab {{ optional($currentChannel)->is($channel) ? 'channel-tab-active' : '' }}">
                            <span>{{ $channel->icon }}</span>
                            <span>{{ $channel->name }}</span>
                        </a>
                    @endforeach
                </nav>

                <!-- 右侧用户操作 -->
                <div class="flex items-center gap-2">
                    @auth
                        <div class="group relative">
                            <button class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <span class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center text-xs font-semibold text-blue-700">
                                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                                </span>
                                <span class="hidden sm:inline max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <!-- 下拉菜单 -->
                            <div class="absolute right-0 top-full mt-1 w-48 rounded-xl border border-gray-200 bg-white py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all shadow-lg">
                                <div class="border-b border-gray-100 px-4 py-2">
                                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ auth()->user()->isAdmin() ? '管理员' : '成员' }}</p>
                                </div>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.articles.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        📝 管理文章
                                    </a>
                                    <a href="{{ route('admin.channels.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        📁 管理频道
                                    </a>
                                    <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        👥 管理用户
                                    </a>
                                    <a href="{{ route('admin.site-settings.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        ⚙️ 站点设置
                                    </a>
                                    <a href="{{ route('admin.devtools.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        🔧 DevTools
                                    </a>
                                @endif
                                <a href="{{ route('settings.subscriptions.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    📮 订阅设置
                                </a>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-50">
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

        <!-- 主内容区 -->
        <main class="mx-auto max-w-6xl px-4 py-6">
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
