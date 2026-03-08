@extends('layouts.app')

@section('content')
    <article class="panel overflow-hidden p-0">
        <div class="bg-gradient-to-r {{ $article->cover_gradient }} p-[1px]">
            <div class="rounded-t-[calc(2rem-1px)] bg-slate-950/95 px-6 py-6 lg:px-8">
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400">
                    <a href="{{ route('channels.show', $article->channel) }}" class="hover:text-cyan-300">{{ $article->channel->icon }} {{ $article->channel->name }}</a>
                    <span>•</span>
                    <span>{{ optional($article->published_at)->format('Y-m-d H:i') }}</span>
                </div>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $article->title }}</h1>
                <p class="mt-4 text-sm leading-7 text-slate-300">{{ $article->excerpt }}</p>
            </div>
        </div>

        <div class="px-6 py-8 lg:px-8">
            <div class="markdown-body">{!! $article->html_body !!}</div>
        </div>
    </article>

    @if($relatedArticles->isNotEmpty())
        <section class="mt-6 panel p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold">同频道推荐</h2>
                <a href="{{ route('channels.show', $article->channel) }}" class="text-sm text-cyan-300 hover:text-cyan-200">查看全部</a>
            </div>
            <div class="space-y-3">
                @foreach($relatedArticles as $related)
                    <a href="{{ route('articles.show', [$related->channel, $related]) }}" class="block rounded-2xl border border-white/8 bg-white/[0.03] p-4 transition hover:border-cyan-400/40 hover:bg-cyan-400/5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="font-medium text-slate-100">{{ $related->title }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ optional($related->published_at)->format('Y-m-d H:i') }}</div>
                            </div>
                            <span class="text-xs text-slate-500">{{ $related->comment_count }} 评论</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section id="comments" class="mt-6 panel p-6 lg:p-8">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-semibold">评论区</h2>
            <span class="text-sm text-slate-400">{{ $article->comments->count() }} 条评论</span>
        </div>

        @auth
            <form action="{{ route('articles.comments.store', $article) }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="body" class="mb-2 block text-sm font-medium text-slate-200">发表评论（支持 Markdown）</label>
                    <textarea id="body" name="body" rows="6" class="input-area">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary">发布评论</button>
            </form>
        @else
            <div class="mt-6 rounded-2xl border border-white/8 bg-white/[0.03] p-5 text-sm text-slate-300">
                游客只能浏览压缩后的静态内容；<a href="{{ route('login') }}" class="text-cyan-300 hover:text-cyan-200">登录后</a>即可参与评论。
            </div>
        @endauth

        <div class="mt-8 space-y-4">
            @forelse($article->comments as $comment)
                <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <strong class="text-slate-100">{{ $comment->user->name }}</strong>
                            <span class="ml-2 text-xs text-slate-500">{{ $comment->user->isAdmin() ? '管理员' : '成员' }}</span>
                        </div>
                        <span class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="markdown-body mt-4">{!! $comment->html_body !!}</div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-white/10 p-6 text-sm text-slate-400">还没有评论，欢迎发表第一条看法。</div>
            @endforelse
        </div>
    </section>
@endsection
