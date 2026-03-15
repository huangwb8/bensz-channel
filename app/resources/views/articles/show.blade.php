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
                @if($article->is_pinned)
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700">置顶</span>
                @endif
                @if($article->is_featured)
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700">精华</span>
                @endif
                <span>•</span>
                <span>{{ optional($article->published_at)->format('Y-m-d H:i') }}</span>
            </div>
            <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $article->title }}</h1>
                    <p class="mt-3 text-gray-600">{{ $article->excerpt }}</p>
                </div>

                @if(auth()->user()?->isAdmin())
                    <x-icon-button
                        :href="route('admin.articles.edit', $article)"
                        icon="pencil"
                        label="编辑文章"
                        title="编辑文章"
                        :aria-label="'编辑文章：'.$article->title"
                        variant="primary"
                        class="shrink-0"
                    />
                @endif
            </div>
            <!-- 作者信息 -->
            <div class="mt-4 flex items-center gap-3">
                <x-user-avatar :user="$article->author" class="h-10 w-10" />
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $article->author->name }}</p>
                    <p class="text-xs text-gray-500">{{ $article->author->isAdmin() ? '管理员' : '成员' }}</p>
                </div>
            </div>
        </div>

        @if(!empty($articleBody['toc']))
            <div class="border-b border-gray-100 px-6 py-4 lg:hidden">
                <details class="article-toc-mobile group rounded-2xl border border-gray-200 bg-gray-50 p-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-gray-900">
                        <span>文章目录</span>
                        <span class="text-xs text-gray-500 transition group-open:rotate-180">⌄</span>
                    </summary>
                    <nav class="mt-3 space-y-1">
                        @foreach($articleBody['toc'] as $item)
                            <a
                                href="#{{ $item['id'] }}"
                                class="article-toc-link block rounded-xl px-3 py-2 text-sm text-gray-600 hover:bg-white hover:text-gray-900"
                                style="--toc-level: {{ max(0, $item['level'] - 1) }}"
                            >
                                <span class="font-medium text-gray-900">{{ $item['number'] }}</span>
                                <span>{{ $item['text'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </details>
            </div>
        @endif

        <!-- 文章正文 -->
        <div class="px-6 py-8 lg:grid lg:grid-cols-[minmax(0,1fr)_18rem] lg:gap-8">
            <div class="min-w-0">
                <div class="markdown-body">{!! $articleBody['html'] !!}</div>
            </div>

            @if(!empty($articleBody['toc']))
                <aside class="article-toc-desktop hidden lg:block">
                    <div class="article-toc-panel sticky top-24 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <h2 class="text-sm font-semibold text-gray-900">文章目录</h2>
                        <nav class="mt-3 space-y-1">
                            @foreach($articleBody['toc'] as $item)
                                <a
                                    href="#{{ $item['id'] }}"
                                    class="article-toc-link block rounded-xl px-3 py-2 text-sm text-gray-600 hover:bg-white hover:text-gray-900"
                                    style="--toc-level: {{ max(0, $item['level'] - 1) }}"
                                >
                                    <span class="font-medium text-gray-900">{{ $item['number'] }}</span>
                                    <span>{{ $item['text'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>
            @endif
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
            <span class="text-sm text-gray-500">{{ $commentCount }} 条评论</span>
        </div>

        @auth
            <form action="{{ route('articles.comments.store', $article) }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="parent_id" value="">
                <div>
                    <label for="body" class="mb-2 block text-sm font-medium text-gray-700">发表评论（支持 Markdown）</label>
                    <div class="markdown-upload-shell" data-markdown-upload-shell>
                        <textarea
                            id="body"
                            name="body"
                            rows="4"
                            class="input-field"
                            placeholder="写下你的看法..."
                            data-image-upload-url="{{ route('uploads.images.store') }}"
                            data-video-upload-url="{{ route('uploads.videos.store') }}"
                            data-upload-context="comment"
                            data-image-upload-label="评论图片"
                            data-video-upload-label="评论视频"
                        >{{ old('body') }}</textarea>
                        <div class="markdown-upload-meta">
                            <p class="markdown-upload-hint">支持 Markdown；聚焦输入框后可直接按 <kbd>Ctrl</kbd> + <kbd>V</kbd> 粘贴图片或不大于 500MB 的视频，媒体会自动上传到站点托管目录并渲染为播放器。</p>
                            <p class="markdown-upload-status" data-markdown-upload-status aria-live="polite" hidden></p>
                        </div>
                    </div>
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
            @forelse($commentTree as $comment)
                @include('articles.partials.comment-item', [
                    'comment' => $comment,
                    'article' => $article,
                    'subscribedCommentIds' => $subscribedCommentIds,
                    'depth' => 0,
                ])
            @empty
                <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">
                    还没有评论，欢迎发表第一条看法。
                </div>
            @endforelse
        </div>
    </section>
@endsection
