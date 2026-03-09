@extends('layouts.app')

@section('content')
    <section class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-orange-900">RSS / SMTP 订阅</h2>
                <p class="mt-1 text-sm text-orange-800">RSS 链接公开可用；SMTP 邮件提醒需要登录后在订阅设置中管理。</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <button
                    data-copy-rss="{{ route('feeds.articles') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-orange-300 bg-white px-3 py-2 text-orange-700 hover:bg-orange-100 transition-colors"
                    title="点击复制 RSS 订阅链接"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7m-6 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                    </svg>
                    <span>RSS</span>
                </button>
                @auth
                    <a href="{{ route('settings.subscriptions.edit') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-white px-3 py-2 text-blue-700 hover:bg-blue-50 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>SMTP</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-white px-3 py-2 text-blue-700 hover:bg-blue-50 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>SMTP</span>
                    </a>
                @endauth
            </div>
        </div>
    </section>

    @if($pinnedArticle)
        <section class="mb-6 rounded-xl border-2 border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-blue-600 px-2.5 py-0.5 text-xs font-semibold text-white">置顶</span>
                        @if($pinnedArticle->is_featured)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">精华</span>
                        @endif
                        <span class="text-xs text-gray-500">{{ $pinnedArticle->channel->icon }} {{ $pinnedArticle->channel->name }}</span>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 truncate">
                        <a href="{{ route('articles.show', [$pinnedArticle->channel, $pinnedArticle]) }}" class="hover:text-blue-600">
                            {{ $pinnedArticle->title }}
                        </a>
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ $pinnedArticle->excerpt }}</p>
                </div>
                <a href="{{ route('articles.show', [$pinnedArticle->channel, $pinnedArticle]) }}" class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    查看详情
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </section>
    @endif

    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">
                @if($currentChannel)
                    {{ $currentChannel->icon }} {{ $currentChannel->name }}
                @else
                    📋 最新帖子
                @endif
            </h2>
            <span class="text-sm text-gray-500">
                共 {{ $latestArticles->count() }} 篇
            </span>
        </div>

        <div class="space-y-3">
            @forelse($latestArticles as $article)
                <a href="{{ route('articles.show', [$article->channel, $article]) }}" class="article-card block">
                    <div class="flex gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <div class="channel-badge">
                                    <span>{{ $article->channel->icon }}</span>
                                    <span>{{ $article->channel->name }}</span>
                                </div>
                                @if($article->is_pinned)
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">置顶</span>
                                @endif
                                @if($article->is_featured)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">精华</span>
                                @endif
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 mb-1 line-clamp-2">
                                {{ $article->title }}
                            </h3>
                            <p class="text-sm text-gray-600 line-clamp-2 mb-3">
                                {{ $article->excerpt }}
                            </p>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    {{ $article->author->name }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ optional($article->published_at)->diffForHumans() }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                    {{ $article->comment_count }} 条评论
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-500">
                    <div class="text-4xl mb-2">📭</div>
                    <p>还没有公开文章，管理员登录后即可发布。</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
