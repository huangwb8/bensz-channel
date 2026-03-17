@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">{{ $article->exists ? '编辑文章' : '发布文章' }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $article->exists ? '修改现有文章内容与状态' : '创建新的文章' }}</p>
            </div>
            <div class="icon-action-group">
                <x-icon-button :href="route('admin.articles.index')" icon="arrow-left" label="返回列表" title="返回列表" />
                @if($article->exists)
                    <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" onsubmit="return confirm('确定删除这篇文章吗？删除后不可恢复。');">
                        @csrf
                        @method('DELETE')
                        <x-icon-button icon="trash" label="删除文章" title="删除文章" :aria-label="'删除文章：'.$article->title" variant="danger" type="submit" />
                    </form>
                @endif
            </div>
        </div>

        <form action="{{ $formAction }}" method="POST" class="mt-8 space-y-5">
            @csrf
            @if($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">频道</label>
                    <select name="channel_id" class="input-field h-11">
                        @foreach($channels as $channel)
                            <option value="{{ $channel->id }}" @selected((int) old('channel_id', $article->channel_id) === $channel->id)>{{ $channel->icon }} {{ $channel->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">精华频道只负责聚合展示；文章主频道仍应选择实际归属频道，再按需勾选“精华文章”。</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">发布时间</label>
                    <input type="datetime-local" name="published_at" class="input-field h-11" value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">标题</label>
                    <input type="text" name="title" class="input-field h-11" value="{{ old('title', $article->title) }}" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Slug <span class="text-gray-400">（URL 路径）</span></label>
                    <input type="text" name="slug" class="input-field h-11" value="{{ old('slug', $article->slug) }}" placeholder="留空自动生成">
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">摘要</label>
                <input type="text" name="excerpt" class="input-field h-11" value="{{ old('excerpt', $article->excerpt) }}" placeholder="留空则自动根据正文提取">
            </div>

            <div>
                <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                    <label class="block text-sm font-medium text-gray-700">标签</label>
                    <a href="{{ route('admin.tags.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">管理标签</a>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($tags as $tag)
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(in_array($tag->id, old('tag_ids', $selectedTagIds), true))>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900">#{{ $tag->name }}</div>
                                <div class="mt-1 text-sm text-gray-500">{{ $tag->description ?: '无标签描述。' }}</div>
                            </div>
                        </label>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500 sm:col-span-2 lg:col-span-3">
                            暂无可用标签，请先前往“标签管理”创建。
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">封面渐变类</label>
                    <input type="text" name="cover_gradient" class="input-field h-11" value="{{ old('cover_gradient', $article->cover_gradient) }}" placeholder="from-blue-500 to-purple-600">
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="inline-flex items-center gap-3 text-sm text-gray-700">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked((bool) old('is_published', $article->is_published))>
                        立即公开发布
                    </label>
                    <label class="inline-flex items-center gap-3 text-sm text-gray-700">
                        <input type="hidden" name="is_pinned" value="0">
                        <input type="checkbox" name="is_pinned" value="1" class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked((bool) old('is_pinned', $article->is_pinned))>
                        置顶文章
                    </label>
                    <label class="inline-flex items-center gap-3 text-sm text-gray-700">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" class="h-5 w-5 rounded border-gray-300 text-amber-500 focus:ring-amber-500" @checked((bool) old('is_featured', $article->is_featured))>
                        精华文章
                    </label>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">正文 <span class="text-gray-400">（支持 Markdown）</span></label>
                <div class="markdown-upload-shell" data-markdown-upload-shell>
                    <textarea
                        name="markdown_body"
                        rows="16"
                        class="input-field font-mono text-sm"
                        data-image-upload-url="{{ route('uploads.images.store') }}"
                        data-video-upload-url="{{ route('uploads.videos.store') }}"
                        data-upload-context="article"
                        data-image-upload-label="文章图片"
                        data-video-upload-label="文章视频"
                    >{{ old('markdown_body', $article->markdown_body) }}</textarea>
                    <div class="markdown-upload-meta">
                        <p class="markdown-upload-hint">支持 Markdown；聚焦正文后可直接按 <kbd>Ctrl</kbd> + <kbd>V</kbd> 粘贴图片或不大于 500MB 的视频，系统会自动上传并插入播放器片段。</p>
                        <p class="markdown-upload-status" data-markdown-upload-status aria-live="polite" hidden></p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">{{ $article->exists ? '保存更新' : '发布文章' }}</button>
                <a href="{{ route('admin.articles.index') }}" class="btn-secondary">取消</a>
            </div>
        </form>
    </section>
@endsection
