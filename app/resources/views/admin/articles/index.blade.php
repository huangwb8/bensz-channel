@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">文章管理</h2>
                <p class="mt-1 text-sm text-gray-500">管理所有频道的文章内容</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">用户管理</a>
                <a href="{{ route('admin.channels.index') }}" class="btn-secondary">频道管理</a>
                <a href="{{ route('admin.articles.create') }}" class="btn-primary">新建文章</a>
            </div>
        </div>
    </section>

    <section class="mt-6 space-y-3">
        @foreach($articles as $article)
            <article class="article-card">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="channel-badge mb-2">
                            <span>{{ $article->channel->icon }}</span>
                            <span>{{ $article->channel->name }}</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $article->title }}</h3>
                        <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $article->excerpt }}</p>
                        <div class="mt-3 flex items-center gap-3 text-xs text-gray-400">
                            <span>{{ optional($article->published_at)->format('Y-m-d H:i') }}</span>
                            <span class="inline-flex items-center rounded-full {{ $article->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }} px-2 py-0.5">
                                {{ $article->is_published ? '已发布' : '草稿' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('articles.show', [$article->channel, $article]) }}" class="btn-secondary">查看</a>
                        <a href="{{ route('admin.articles.edit', $article) }}" class="btn-primary">编辑</a>
                    </div>
                </div>
            </article>
        @endforeach
    </section>
@endsection
