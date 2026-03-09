@extends('layouts.app')

@section('content')
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex items-center gap-3">
            <span class="text-3xl">{{ $currentChannel->icon }}</span>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $currentChannel->name }}</h1>
                <p class="text-sm text-gray-500">{{ $currentChannel->description }}</p>
            </div>
            <div class="ml-auto flex flex-wrap items-center justify-end gap-2 text-sm text-gray-500">
                <span>共 {{ $channelArticles->count() }} 篇文章</span>
                <button
                    data-copy-rss="{{ route('feeds.channels.show', $currentChannel) }}"
                    class="icon-action"
                    title="点击复制 RSS 订阅链接"
                    aria-label="复制 RSS 订阅链接"
                >
                    <x-icon name="rss" class="h-5 w-5" />
                    <span class="sr-only">RSS 订阅本版块</span>
                </button>
                @auth
                    <x-icon-button :href="route('settings.subscriptions.edit')" icon="mail" label="SMTP 订阅设置" title="SMTP 订阅设置" />
                @endif
            </div>
        </div>
    </section>

    <section class="space-y-3">
        @forelse($channelArticles as $article)
            <a href="{{ route('articles.show', [$article->channel, $article]) }}" class="article-card block">
                <div class="flex gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            @if($currentChannel->isFeaturedChannel())
                                <div class="channel-badge">
                                    <span>{{ $article->channel->icon }}</span>
                                    <span>{{ $article->channel->name }}</span>
                                </div>
                            @endif
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
                <p>这个频道还没有内容，稍后再来看看。</p>
            </div>
        @endforelse
    </section>
@endsection
