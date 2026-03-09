@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">文章管理</h2>
                <p class="mt-1 text-sm text-gray-500">管理所有频道的文章内容，并可一键切换置顶 / 精华状态。</p>
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
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <div class="channel-badge">
                                <span>{{ $article->channel->icon }}</span>
                                <span>{{ $article->channel->name }}</span>
                            </div>
                            @if($article->is_pinned)
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">置顶</span>
                            @endif
                            @if($article->is_featured)
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">精华</span>
                            @endif
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
                        <form action="{{ route('admin.articles.pin', $article) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <x-icon-button
                                icon="pin"
                                :label="$article->is_pinned ? '取消置顶' : '设为置顶'"
                                :title="$article->is_pinned ? '取消置顶' : '设为置顶'"
                                :aria-label="'切换置顶：'.$article->title"
                                :variant="$article->is_pinned ? 'primary' : 'default'"
                                type="submit"
                            />
                        </form>
                        <form action="{{ route('admin.articles.feature', $article) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <x-icon-button
                                icon="star"
                                :label="$article->is_featured ? '取消精华' : '设为精华'"
                                :title="$article->is_featured ? '取消精华' : '设为精华'"
                                :aria-label="'切换精华：'.$article->title"
                                :variant="$article->is_featured ? 'primary' : 'default'"
                                type="submit"
                            />
                        </form>
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
