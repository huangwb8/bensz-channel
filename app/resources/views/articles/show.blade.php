@extends('layouts.app')

@section('content')
    <!-- 返回链接 -->
    <div class="mb-4">
        <a href="{{ route('channels.show', $article->channel) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            返回 {{ $article->channel->name }}
        </a>
    </div>

    <!-- 文章内容 -->
    <article class="rounded-xl border border-gray-200 bg-white overflow-hidden">
        <!-- 文章头部 -->
        <div class="border-b border-gray-100 p-6">
            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 mb-3">
                <span class="channel-badge">
                    <span>{{ $article->channel->icon }}</span>
                    <span>{{ $article->channel->name }}</span>
                </span>
                <span>•</span>
                <span>{{ optional($article->published_at)->format('Y-m-d H:i') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $article->title }}</h1>
            <p class="mt-3 text-gray-600">{{ $article->excerpt }}</p>

            <!-- 作者信息 -->
            <div class="mt-4 flex items-center gap-3">
                <span class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-semibold text-blue-700">
                    {{ mb_substr($article->author->name, 0, 1) }}
                </span>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $article->author->name }}</p>
                    <p class="text-xs text-gray-500">{{ $article->author->isAdmin() ? '管理员' : '成员' }}</p>
                </div>
            </div>
        </div>

        <!-- 文章正文 -->
        <div class="px-6 py-8">
            <div class="markdown-body">{!! $article->html_body !!}</div>
        </div>
    </article>

    <!-- 同频道推荐 -->
    @if($relatedArticles->isNotEmpty())
        <section class="mt-6 rounded-xl border border-gray-200 bg-white p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">同频道推荐</h2>
                <a href="{{ route('channels.show', $article->channel) }}" class="text-sm text-blue-600 hover:text-blue-700">
                    查看全部 →
                </a>
            </div>
            <div class="space-y-2">
                @foreach($relatedArticles as $related)
                    <a href="{{ route('articles.show', [$related->channel, $related]) }}" class="block rounded-lg border border-gray-100 bg-gray-50 p-3 transition hover:bg-gray-100">
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-gray-900 truncate">{{ $related->title }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ optional($related->published_at)->format('Y-m-d') }}</div>
                            </div>
                            <span class="shrink-0 text-xs text-gray-400">{{ $related->comment_count }} 评论</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- 评论区 -->
    <section id="comments" class="mt-6 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">评论区</h2>
            <span class="text-sm text-gray-500">{{ $article->comments->count() }} 条评论</span>
        </div>

        @auth
            <form action="{{ route('articles.comments.store', $article) }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="body" class="mb-2 block text-sm font-medium text-gray-700">发表评论（支持 Markdown）</label>
                    <textarea id="body" name="body" rows="4" class="input-field" placeholder="写下你的看法...">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary">发布评论</button>
            </form>
        @else
            <div class="mt-5 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                游客只能浏览内容；<a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-800 underline">登录后</a>即可参与评论。
            </div>
        @endauth

        <!-- 评论列表 -->
        <div class="mt-6 space-y-4">
            @forelse($article->comments as $comment)
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-xs font-semibold text-blue-700">
                                {{ mb_substr($comment->user->name, 0, 1) }}
                            </span>
                            <div>
                                <span class="text-sm font-medium text-gray-900">{{ $comment->user->name }}</span>
                                <span class="ml-2 text-xs text-gray-400">{{ $comment->user->isAdmin() ? '管理员' : '成员' }}</span>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="markdown-body mt-3 text-sm">{!! $comment->html_body !!}</div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">
                    还没有评论，欢迎发表第一条看法。
                </div>
            @endforelse
        </div>
    </section>
@endsection
