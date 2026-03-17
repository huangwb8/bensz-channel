@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">标签管理</h2>
                <p class="mt-1 text-sm text-gray-500">标签由管理员统一定义，文章可绑定多个标签，成员可按标签订阅邮件与 RSS。</p>
            </div>
            <div class="icon-action-group">
                <x-icon-button :href="route('admin.articles.index')" icon="document" label="文章管理" title="文章管理" />
                <x-icon-button :href="route('admin.channels.index')" icon="folder" label="频道管理" title="频道管理" />
                <x-icon-button :href="route('admin.comments.index')" icon="chat-bubble-left-right" label="评论管理" title="评论管理" />
                <x-icon-button :href="route('admin.users.index')" icon="users" label="用户管理" title="用户管理" />
            </div>
        </div>

        <form action="{{ route('admin.tags.store') }}" method="POST" class="mt-6 rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-6 shadow-sm">
            @csrf

            <h3 class="mb-5 text-lg font-semibold text-gray-900">新增标签</h3>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">标签名称 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="input-field h-11" placeholder="例如：Laravel" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">URL Slug</label>
                    <input type="text" name="slug" class="input-field h-11" placeholder="留空自动生成">
                </div>
            </div>

            <div class="mt-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">标签描述</label>
                <input type="text" name="description" class="input-field h-11" placeholder="说明这个标签适用于哪些文章">
            </div>

            <button type="submit" class="btn-primary mt-6">新增标签</button>
        </form>
    </section>

    <section class="mt-6 space-y-3">
        @forelse($tags as $tag)
            <div class="article-card">
                <div class="flex flex-wrap items-start gap-4">
                    <div class="flex min-w-0 flex-1 flex-wrap items-start justify-between gap-4">
                        <form action="{{ route('admin.tags.update', $tag) }}" method="POST" class="min-w-0 flex-1">
                            @csrf
                            @method('PUT')

                            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1.8fr)_auto]">
                                <input type="text" name="name" value="{{ $tag->name }}" class="input-field h-10" aria-label="标签名称">
                                <input type="text" name="slug" value="{{ $tag->slug }}" class="input-field h-10" aria-label="标签 Slug">
                                <input type="text" name="description" value="{{ $tag->description }}" class="input-field h-10" aria-label="标签描述">
                                <div class="flex items-center justify-end gap-2">
                                    <x-icon-button
                                        icon="save"
                                        label="保存标签"
                                        title="保存标签"
                                        :aria-label="'保存标签：'.$tag->name"
                                        variant="primary"
                                        type="submit"
                                    />
                                    <x-icon-button
                                        icon="trash"
                                        label="删除标签"
                                        title="删除标签"
                                        :aria-label="'删除标签：'.$tag->name"
                                        variant="danger"
                                        type="submit"
                                        form="delete-tag-{{ $tag->id }}"
                                    />
                                </div>
                            </div>
                        </form>

                        <form id="delete-tag-{{ $tag->id }}" action="{{ route('admin.tags.destroy', $tag) }}" method="POST" class="hidden" onsubmit="return confirm('确认删除该标签？相关文章的标签绑定与标签订阅都会一并清理。')">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700">#{{ $tag->name }}</span>
                    <span>文章 {{ $tag->articles_count }}</span>
                    <span>订阅 {{ $tag->email_subscriptions_count }}</span>
                    <button
                        data-copy-rss="{{ route('feeds.tags.show', $tag) }}"
                        class="inline-flex items-center gap-1 text-orange-600 transition-colors hover:text-orange-700"
                        title="点击复制标签 RSS 链接"
                    >
                        <x-icon name="rss" class="h-3.5 w-3.5" />
                        <span>RSS 链接</span>
                    </button>
                </div>
            </div>
        @empty
            <section class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                还没有标签，先新增一个吧。
            </section>
        @endforelse
    </section>
@endsection
