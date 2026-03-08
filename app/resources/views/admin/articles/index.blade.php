@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">文章管理</h2>
                <p class="mt-1 text-sm text-gray-500">管理所有频道的文章内容</p>
            </div>
            <div class="icon-action-group">
                <x-icon-button :href="route('admin.users.index')" icon="users" label="用户管理" title="用户管理" />
                <x-icon-button :href="route('admin.channels.index')" icon="folder" label="频道管理" title="频道管理" />
                <x-icon-button :href="route('admin.articles.create')" icon="plus" label="新建文章" title="新建文章" variant="primary" />
            </div>
        </div>
    </section>

    <section class="mt-6 space-y-3">
        @forelse($articles as $article)
            <article class="article-card">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="channel-badge mb-2">
                            <span>{{ $article->channel->icon }}</span>
                            <span>{{ $article->channel->name }}</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $article->title }}</h3>
                        <p class="mt-2 line-clamp-2 text-sm text-gray-500">{{ $article->excerpt }}</p>
                        <div class="mt-3 flex items-center gap-3 text-xs text-gray-400">
                            <span>{{ optional($article->published_at)->format('Y-m-d H:i') }}</span>
                            <span class="inline-flex items-center rounded-full {{ $article->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }} px-2 py-0.5">
                                {{ $article->is_published ? '已发布' : '草稿' }}
                            </span>
                        </div>
                    </div>
                    <div class="icon-action-group">
                        <x-icon-button
                            :href="route('articles.show', [$article->channel, $article])"
                            icon="eye"
                            label="查看文章"
                            title="查看文章"
                            :aria-label="'查看文章：'.$article->title"
                        />
                        <x-icon-button
                            :href="route('admin.articles.edit', $article)"
                            icon="pencil"
                            label="编辑文章"
                            title="编辑文章"
                            :aria-label="'编辑文章：'.$article->title"
                            variant="primary"
                        />
                        <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" onsubmit="return confirm('确定删除这篇文章吗？删除后不可恢复。');">
                            @csrf
                            @method('DELETE')
                            <x-icon-button
                                icon="trash"
                                label="删除文章"
                                title="删除文章"
                                :aria-label="'删除文章：'.$article->title"
                                variant="danger"
                                type="submit"
                            />
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <section class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                还没有文章，点击右上角“新建文章”即可开始发布。
            </section>
        @endforelse
    </section>
@endsection
