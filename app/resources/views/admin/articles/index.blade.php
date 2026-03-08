@extends('layouts.app')

@section('content')
    <section class="panel p-6 lg:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-violet-300/80">管理员</p>
                <h2 class="mt-3 text-3xl font-semibold">文章管理</h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.channels.index') }}" class="btn-secondary">频道管理</a>
                <a href="{{ route('admin.articles.create') }}" class="btn-primary">新建文章</a>
            </div>
        </div>
    </section>

    <section class="mt-6 space-y-4">
        @foreach($articles as $article)
            <article class="panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-500">{{ $article->channel->name }}</p>
                        <h3 class="mt-2 text-xl font-semibold">{{ $article->title }}</h3>
                        <p class="mt-3 text-sm text-slate-400">{{ $article->excerpt }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('articles.show', [$article->channel, $article]) }}" class="btn-secondary">查看</a>
                        <a href="{{ route('admin.articles.edit', $article) }}" class="btn-primary">编辑</a>
                    </div>
                </div>
            </article>
        @endforeach
    </section>
@endsection
