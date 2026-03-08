@extends('layouts.app')

@section('content')
    <section class="panel p-6 lg:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-violet-300/80">管理员</p>
                <h2 class="mt-3 text-3xl font-semibold">{{ $article->exists ? '编辑文章' : '发布文章' }}</h2>
            </div>
            <a href="{{ route('admin.articles.index') }}" class="btn-secondary">返回列表</a>
        </div>

        <form action="{{ $formAction }}" method="POST" class="mt-8 grid gap-4">
            @csrf
            @if($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">频道</label>
                    <select name="channel_id" class="input-area h-12">
                        @foreach($channels as $channel)
                            <option value="{{ $channel->id }}" @selected((int) old('channel_id', $article->channel_id) === $channel->id)>{{ $channel->icon }} {{ $channel->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">发布时间</label>
                    <input type="datetime-local" name="published_at" class="input-area h-12" value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">标题</label>
                    <input type="text" name="title" class="input-area h-12" value="{{ old('title', $article->title) }}" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">Slug</label>
                    <input type="text" name="slug" class="input-area h-12" value="{{ old('slug', $article->slug) }}">
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200">摘要</label>
                <input type="text" name="excerpt" class="input-area h-12" value="{{ old('excerpt', $article->excerpt) }}" placeholder="留空则自动根据正文提取">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200">封面渐变类</label>
                <input type="text" name="cover_gradient" class="input-area h-12" value="{{ old('cover_gradient', $article->cover_gradient) }}">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200">正文（Markdown）</label>
                <textarea name="markdown_body" rows="18" class="input-area">{{ old('markdown_body', $article->markdown_body) }}</textarea>
            </div>

            <label class="inline-flex items-center gap-3 text-sm text-slate-200">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" class="h-4 w-4 rounded border-white/10 bg-slate-900" @checked((bool) old('is_published', $article->is_published))>
                立即公开发布
            </label>

            <div>
                <button type="submit" class="btn-primary">{{ $article->exists ? '保存更新' : '发布文章' }}</button>
            </div>
        </form>
    </section>
@endsection
