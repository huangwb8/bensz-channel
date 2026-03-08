@extends('layouts.app')

@section('content')
    <section class="panel overflow-hidden p-0">
        <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:p-8">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-cyan-300/80">社区总览</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">一个接近 QQ 频道体验的 Web 社区雏形</h2>
                <p class="mt-4 max-w-3xl text-base leading-7 text-slate-300">
                    基于 PHP、PostgreSQL、Redis 与 Docker 构建；管理员发文、成员评论、游客静态访问三条主路径已经打通。
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="btn-primary">立即登录</a>
                    @if($featuredArticle)
                        <a href="{{ route('articles.show', [$featuredArticle->channel, $featuredArticle]) }}" class="btn-secondary">查看最新公告</a>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-white/10 bg-slate-900/80 p-5">
                <p class="text-sm font-semibold text-slate-200">登录方式</p>
                <div class="mt-4 space-y-3 text-sm text-slate-300">
                    <div class="rounded-2xl border border-white/8 bg-white/[0.03] p-4">邮箱验证码登录</div>
                    <div class="rounded-2xl border border-white/8 bg-white/[0.03] p-4">手机号验证码登录</div>
                    <div class="rounded-2xl border border-white/8 bg-white/[0.03] p-4">微信 / QQ 扫码演示登录</div>
                </div>
            </div>
        </div>
    </section>

    @if($featuredArticle)
        <section class="mt-6 panel p-6 lg:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.35em] text-violet-300/80">推荐内容</p>
                    <h2 class="mt-2 text-2xl font-semibold">{{ $featuredArticle->title }}</h2>
                </div>
                <a href="{{ route('articles.show', [$featuredArticle->channel, $featuredArticle]) }}" class="btn-secondary">打开文章</a>
            </div>
            <p class="mt-4 text-sm leading-7 text-slate-300">{{ $featuredArticle->excerpt }}</p>
        </section>
    @endif

    <section class="mt-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-xl font-semibold">最新文章</h2>
            <span class="text-sm text-slate-400">按发布时间倒序</span>
        </div>
        <div class="space-y-4">
            @forelse($latestArticles as $article)
                @include('partials.article-card', ['article' => $article])
            @empty
                <div class="panel p-6 text-slate-400">还没有公开文章，管理员登录后即可发布。</div>
            @endforelse
        </div>
    </section>
@endsection
