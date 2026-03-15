@extends('layouts.app')

@section('content')
    <div data-admin-comments-page>
        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">评论管理</h2>
                    <p class="mt-1 text-sm text-gray-500">集中查看全站评论，支持按内容、用户与文章筛选，并可快速回复、显示、隐藏或删除。</p>
                </div>
                <div class="icon-action-group">
                    <x-icon-button :href="route('admin.articles.index')" icon="document" label="文章管理" title="文章管理" />
                    <x-icon-button :href="route('admin.channels.index')" icon="folder" label="频道管理" title="频道管理" />
                    <x-icon-button :href="route('admin.users.index')" icon="users" label="用户管理" title="用户管理" />
                </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">评论总数</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</div>
                </div>
                <div class="rounded-xl border border-green-100 bg-green-50 p-4">
                    <div class="text-xs text-green-700">当前显示</div>
                    <div class="mt-2 text-2xl font-semibold text-green-900">{{ $stats['visible'] }}</div>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                    <div class="text-xs text-amber-700">当前隐藏</div>
                    <div class="mt-2 text-2xl font-semibold text-amber-900">{{ $stats['hidden'] }}</div>
                </div>
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <div class="text-xs text-blue-700">7 天新增</div>
                    <div class="mt-2 text-2xl font-semibold text-blue-900">{{ $stats['recent'] }}</div>
                </div>
            </div>

            <form action="{{ route('admin.comments.index') }}" method="GET" class="mt-6 grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-5 lg:grid-cols-[minmax(0,2fr)_180px_auto]">
                <input
                    type="text"
                    name="q"
                    value="{{ $filters['q'] }}"
                    class="input-field h-11"
                    placeholder="搜索评论内容、用户昵称或文章标题"
                >
                <select name="visibility" class="input-field h-11">
                    <option value="">全部可见性</option>
                    <option value="visible" @selected($filters['visibility'] === 'visible')>仅显示</option>
                    <option value="hidden" @selected($filters['visibility'] === 'hidden')>仅隐藏</option>
                </select>
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary">筛选</button>
                    <a href="{{ route('admin.comments.index') }}" class="btn-secondary">重置</a>
                </div>
            </form>
        </section>

        <section class="mt-6 space-y-3">
            @forelse($comments as $comment)
                <article class="article-card">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                    {{ $comment->user->name }}
                                </span>
                                <span class="inline-flex items-center rounded-full {{ $comment->is_visible ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }} px-2.5 py-1 text-xs font-medium">
                                    {{ $comment->is_visible ? '显示中' : '已隐藏' }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $comment->created_at?->format('Y-m-d H:i') }}</span>
                            </div>

                            <h3 class="mt-3 text-lg font-semibold text-gray-900">{{ $comment->article->title }}</h3>
                            <p class="mt-1 text-sm text-gray-500">频道：{{ $comment->article->channel->name }}</p>
                            <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-gray-700">{{ $comment->markdown_body }}</p>
                        </div>

                        <div class="shrink-0 space-y-3">
                            <div class="icon-action-group">
                                <x-icon-button
                                    :href="route('articles.show', [$comment->article->channel, $comment->article]).'#comments'"
                                    icon="eye"
                                    label="查看评论"
                                    title="查看评论"
                                    :aria-label="'查看评论：'.$comment->article->title"
                                />
                                <form action="{{ route('admin.comments.visibility', $comment) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                    <input type="hidden" name="visibility" value="{{ $filters['visibility'] }}">
                                    <input type="hidden" name="is_visible" value="{{ $comment->is_visible ? '0' : '1' }}">
                                    <x-icon-button
                                        icon="chat-bubble-left-right"
                                        :label="$comment->is_visible ? '隐藏评论' : '显示评论'"
                                        :title="$comment->is_visible ? '隐藏评论' : '显示评论'"
                                        :aria-label="($comment->is_visible ? '隐藏评论：' : '显示评论：').$comment->article->title"
                                        :variant="$comment->is_visible ? 'default' : 'primary'"
                                        type="submit"
                                    />
                                </form>
                                <form action="{{ route('admin.comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('确认删除这条评论吗？删除后不可恢复。');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                    <input type="hidden" name="visibility" value="{{ $filters['visibility'] }}">
                                    <x-icon-button
                                        icon="trash"
                                        label="删除评论"
                                        title="删除评论"
                                        :aria-label="'删除评论：'.$comment->article->title"
                                        variant="danger"
                                        type="submit"
                                    />
                                </form>
                            </div>

                            <details class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3">
                                <summary class="cursor-pointer list-none text-sm font-medium text-gray-700">
                                    回复评论
                                </summary>
                                <form action="{{ route('articles.comments.store', $comment->article) }}" method="POST" class="mt-3 space-y-3">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                    <input type="hidden" name="redirect_back" value="1">
                                    <div class="markdown-upload-shell" data-markdown-upload-shell>
                                        <textarea
                                            name="body"
                                            rows="3"
                                            class="input-field"
                                            placeholder="直接在这里回复这条评论..."
                                            data-image-upload-url="{{ route('uploads.images.store') }}"
                                            data-video-upload-url="{{ route('uploads.videos.store') }}"
                                            data-upload-context="comment"
                                            data-image-upload-label="评论图片"
                                            data-video-upload-label="评论视频"
                                        ></textarea>
                                        <div class="markdown-upload-meta">
                                            <p class="markdown-upload-hint">支持 Markdown，可继续粘贴图片或视频。</p>
                                            <p class="markdown-upload-status" data-markdown-upload-status aria-live="polite" hidden></p>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700">
                                            提交回复
                                        </button>
                                    </div>
                                </form>
                            </details>
                        </div>
                    </div>
                </article>
            @empty
                <section class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                    当前筛选条件下暂无评论记录。
                </section>
            @endforelse
        </section>

        <div class="mt-6">
            {{ $comments->links() }}
        </div>
    </div>
@endsection
