@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">频道管理</h2>
                <p class="mt-1 text-sm text-gray-500">管理社区的可编辑频道，系统保留频道将自动维护。</p>
            </div>
            <div class="icon-action-group">
                <x-icon-button :href="route('admin.articles.index')" icon="document" label="文章管理" title="文章管理" />
                <x-icon-button :href="route('admin.users.index')" icon="users" label="用户管理" title="用户管理" />
            </div>
        </div>

        <form action="{{ route('admin.channels.store') }}" method="POST" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-5">
            @csrf
            <h3 class="mb-4 font-medium text-gray-900">新增频道</h3>
            <div class="grid gap-4 lg:grid-cols-7">
                <input type="text" name="name" class="input-field h-10" placeholder="频道名称" required>
                <input type="text" name="slug" class="input-field h-10" placeholder="slug（留空自动生成）">
                <input type="text" name="description" class="input-field h-10 lg:col-span-2" placeholder="频道简介">
                <input type="text" name="icon" class="input-field h-10" placeholder="图标" value="#">
                <input type="text" name="accent_color" class="input-field h-10" placeholder="#8b5cf6" value="#8b5cf6">
                <input type="number" name="sort_order" class="input-field h-10" placeholder="排序" value="0">
            </div>
            <button type="submit" class="btn-primary mt-4">新增频道</button>
        </form>

        <div class="mt-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            未分类频道仍会自动接收迁移文章，无需单独维护。
        </div>
    </section>

    <section class="mt-6 space-y-3">
        @forelse($channels as $channel)
            <div class="article-card">
                <form action="{{ route('admin.channels.update', $channel) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid items-center gap-4 lg:grid-cols-8">
                        <input type="text" name="name" value="{{ $channel->name }}" class="input-field h-10">
                        <input type="text" name="slug" value="{{ $channel->slug }}" class="input-field h-10">
                        <input type="text" name="description" value="{{ $channel->description }}" class="input-field h-10 lg:col-span-2">
                        <input type="text" name="icon" value="{{ $channel->icon }}" class="input-field h-10 text-center">
                        <input type="text" name="accent_color" value="{{ $channel->accent_color }}" class="input-field h-10">
                        <input type="number" name="sort_order" value="{{ $channel->sort_order }}" class="input-field h-10">
                        <div class="flex items-center justify-end gap-2">
                            <x-icon-button
                                icon="save"
                                label="保存频道"
                                title="保存频道"
                                :aria-label="'保存频道：'.$channel->name"
                                variant="primary"
                                type="submit"
                            />
                            <x-icon-button
                                icon="trash"
                                label="删除频道"
                                title="删除频道"
                                :aria-label="'删除频道：'.$channel->name"
                                variant="danger"
                                type="submit"
                                form="delete-channel-{{ $channel->id }}"
                            />
                        </div>
                    </div>
                </form>
                <form id="delete-channel-{{ $channel->id }}" action="{{ route('admin.channels.destroy', $channel) }}" method="POST" class="hidden" onsubmit="return confirm('确认删除该频道？文章将自动归入未分类。')">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        @empty
            <section class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                当前只有系统保留频道，先新增一个可管理频道吧。
            </section>
        @endforelse
    </section>
@endsection
