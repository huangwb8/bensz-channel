@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">{{ $article->exists ? '编辑文章' : '发布文章' }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $article->exists ? '修改现有文章内容' : '创建新的文章' }}</p>
            </div>
            <a href="{{ route('admin.articles.index') }}" class="btn-secondary">返回列表</a>
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

            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">封面渐变类</label>
                    <input type="text" name="cover_gradient" class="input-field h-11" value="{{ old('cover_gradient', $article->cover_gradient) }}" placeholder="from-blue-500 to-purple-600">
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-3 text-sm text-gray-700">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked((bool) old('is_published', $article->is_published))>
                        立即公开发布
                    </label>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">正文 <span class="text-gray-400">（支持 Markdown）</span></label>
                <textarea name="markdown_body" rows="16" class="input-field font-mono text-sm">{{ old('markdown_body', $article->markdown_body) }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">{{ $article->exists ? '保存更新' : '发布文章' }}</button>
                <a href="{{ route('admin.articles.index') }}" class="btn-secondary">取消</a>
            </div>
        </form>
    </section>
@endsection
