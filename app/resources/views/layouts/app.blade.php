<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle ? $pageTitle.' · '.$siteName : $siteName }}</title>
        <meta name="description" content="{{ $siteTagline }}">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-slate-950 text-slate-100 antialiased">
        <div class="mx-auto min-h-screen max-w-[1680px] px-4 py-4 sm:px-6 xl:grid xl:grid-cols-[280px_minmax(0,1fr)_320px] xl:gap-6">
            <aside class="mb-4 space-y-4 xl:mb-0">
                <div class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.35em] text-violet-300/80">Bensz Channel</p>
                            <h1 class="mt-2 text-2xl font-semibold">{{ $siteName }}</h1>
                        </div>
                        @if(($staticPage ?? false) === true)
                            <span class="badge">静态快照</span>
                        @endif
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-300">{{ $siteTagline }}</p>
                </div>

                <div class="panel p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-200">频道导航</h2>
                        @auth
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.channels.index') }}" class="text-xs text-cyan-300 hover:text-cyan-200">管理</a>
                            @endif
                        @endauth
                    </div>

                    <div class="space-y-2">
                        <a href="{{ route('home') }}" class="channel-link {{ $currentChannel === null ? 'channel-link-active' : '' }}">
                            <span class="text-lg">🏠</span>
                            <span>
                                <strong class="block text-sm">总览</strong>
                                <span class="text-xs text-slate-400">最新公告与频道动态</span>
                            </span>
                        </a>

                        @foreach($channels as $channel)
                            <a href="{{ route('channels.show', $channel) }}" class="channel-link {{ optional($currentChannel)->is($channel) ? 'channel-link-active' : '' }}">
                                <span class="text-lg">{{ $channel->icon }}</span>
                                <span class="min-w-0 flex-1">
                                    <strong class="block truncate text-sm">{{ $channel->name }}</strong>
                                    <span class="text-xs text-slate-400">{{ $channel->articles_count }} 篇内容</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>

            <main class="min-w-0">
                @if (session('status'))
                    <div class="mb-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('otp_preview'))
                    <div class="mb-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                        开发环境验证码预览：<span class="font-semibold tracking-[0.25em]">{{ session('otp_preview') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>

            <aside class="mt-6 space-y-4 xl:mt-0">
                <div class="panel p-4">
                    @auth
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-cyan-300/80">当前身份</p>
                                <h3 class="mt-2 text-lg font-semibold">{{ auth()->user()->name }}</h3>
                                <p class="mt-1 text-sm text-slate-400">{{ auth()->user()->isAdmin() ? '管理员' : '已登录成员' }}</p>
                            </div>
                            <span class="badge">在线</span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.articles.index') }}" class="btn-primary">管理文章</a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-secondary">退出登录</button>
                            </form>
                        </div>
                    @else
                        <p class="text-xs uppercase tracking-[0.3em] text-violet-300/80">游客模式</p>
                        <h3 class="mt-2 text-lg font-semibold">你正在浏览静态页面</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">登录后可发表评论；管理员登录后可继续发文与管理频道。</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('login') }}" class="btn-primary">登录 / 注册</a>
                        </div>
                    @endauth
                </div>

                <div class="panel p-4">
                    <h3 class="text-sm font-semibold text-slate-200">社区指标</h3>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="stat-card"><span>{{ $stats['channels'] }}</span><small>频道</small></div>
                        <div class="stat-card"><span>{{ $stats['articles'] }}</span><small>文章</small></div>
                        <div class="stat-card"><span>{{ $stats['comments'] }}</span><small>评论</small></div>
                        <div class="stat-card"><span>{{ $stats['members'] }}</span><small>成员</small></div>
                    </div>
                </div>

                <div class="panel p-4">
                    <h3 class="text-sm font-semibold text-slate-200">最新评论</h3>
                    <div class="mt-4 space-y-3">
                        @forelse($recentComments as $comment)
                            <a href="{{ route('articles.show', [$comment->article->channel, $comment->article]) }}#comments" class="block rounded-2xl border border-white/6 bg-white/[0.03] p-3 transition hover:border-cyan-400/40 hover:bg-cyan-400/5">
                                <div class="flex items-center justify-between gap-3">
                                    <strong class="truncate text-sm">{{ $comment->user->name }}</strong>
                                    <span class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-1 truncate text-xs text-slate-400">{{ $comment->article->title }}</p>
                                <p class="mt-2 text-sm text-slate-300">{{ \Illuminate\Support\Str::limit(strip_tags($comment->html_body), 72) }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-400">还没有评论，来抢个沙发吧。</p>
                        @endforelse
                    </div>
                </div>

                @if(isset($article))
                    <div class="panel p-4">
                        <h3 class="text-sm font-semibold text-slate-200">当前文章</h3>
                        <dl class="mt-4 space-y-3 text-sm text-slate-300">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-400">频道</dt>
                                <dd>{{ $article->channel->name }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-400">作者</dt>
                                <dd>{{ $article->author->name }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-400">评论</dt>
                                <dd>{{ $article->comment_count }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-400">发布时间</dt>
                                <dd>{{ optional($article->published_at)->format('Y-m-d H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </aside>
        </div>
    </body>
</html>
