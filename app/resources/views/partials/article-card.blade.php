<article class="panel p-5">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="mb-3 inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">
                {{ $article->channel->icon }} {{ $article->channel->name }}
            </div>
            <h3 class="text-xl font-semibold text-white">
                <a href="{{ route('articles.show', [$article->channel, $article]) }}" class="hover:text-cyan-300">{{ $article->title }}</a>
            </h3>
            <p class="mt-3 text-sm leading-6 text-slate-300">{{ $article->excerpt }}</p>
        </div>
        <div class="hidden rounded-3xl bg-gradient-to-br {{ $article->cover_gradient }} p-[1px] lg:block">
            <div class="rounded-[calc(1.5rem-1px)] bg-slate-950/90 px-4 py-3 text-right">
                <div class="text-xs uppercase tracking-[0.3em] text-slate-400">作者</div>
                <div class="mt-2 font-medium">{{ $article->author->name }}</div>
            </div>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-4 text-xs text-slate-400">
        <span>{{ optional($article->published_at)->format('Y-m-d H:i') }}</span>
        <span>{{ $article->comment_count }} 条评论</span>
        <a href="{{ route('articles.show', [$article->channel, $article]) }}" class="text-cyan-300 hover:text-cyan-200">阅读全文</a>
    </div>
</article>
