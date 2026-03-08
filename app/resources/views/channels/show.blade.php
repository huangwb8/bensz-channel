@extends('layouts.app')

@section('content')
    <section class="panel p-6 lg:p-8">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-cyan-300/80">频道</p>
                <h2 class="mt-3 flex items-center gap-3 text-3xl font-semibold">
                    <span>{{ $currentChannel->icon }}</span>
                    <span>{{ $currentChannel->name }}</span>
                </h2>
                <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-300">{{ $currentChannel->description }}</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-slate-300">
                共 {{ $channelArticles->count() }} 篇公开文章
            </div>
        </div>
    </section>

    <section class="mt-6 space-y-4">
        @forelse($channelArticles as $article)
            @include('partials.article-card', ['article' => $article])
        @empty
            <div class="panel p-6 text-slate-400">这个频道还没有内容，稍后再来看看。</div>
        @endforelse
    </section>
@endsection
