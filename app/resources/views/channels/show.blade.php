@extends('layouts.app')

@section('content')
    <!-- 频道信息 -->
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex items-center gap-3">
            <span class="text-3xl">{{ $currentChannel->icon }}</span>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $currentChannel->name }}</h1>
                <p class="text-sm text-gray-500">{{ $currentChannel->description }}</p>
            </div>
            <div class="ml-auto text-sm text-gray-500">
                共 {{ $channelArticles->count() }} 篇文章
            </div>
        </div>
    </section>

    <!-- 帖子列表 -->
    <section class="space-y-3">
        @forelse($channelArticles as $article)
            <article class="article-card">
                <div class="flex gap-4">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">
                            <a href="{{ route('articles.show', [$article->channel, $article]) }}" class="hover:text-blue-600 line-clamp-2">
                                {{ $article->title }}
                            </a>
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
            </article>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-500">
                <div class="text-4xl mb-2">📭</div>
                <p>这个频道还没有内容，稍后再来看看。</p>
            </div>
        @endforelse
    </section>
@endsection
