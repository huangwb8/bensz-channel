@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">频道管理</h2>
                <p class="mt-1 text-sm text-gray-500">管理社区的所有频道</p>
            </div>
            <div class="icon-action-group">
                <x-icon-button :href="route('admin.users.index')" icon="users" label="用户管理" title="用户管理" />
                <x-icon-button :href="route('admin.articles.index')" icon="document" label="文章管理" title="文章管理" />
            </div>
        </div>

        <!-- 新增频道表单 -->
        <form action="{{ route('admin.channels.store') }}" method="POST" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-5">
            @csrf
            <h3 class="font-medium text-gray-900 mb-4">新增频道</h3>
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
    </section>

    <!-- 频道列表 -->
    <section class="mt-6 space-y-3">
        @foreach($channels as $channel)
            @php($reserved = in_array($channel->slug, ['all','uncategorized'], true))
            <div class="article-card">
                <form action="{{ route('admin.channels.update', $channel) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 lg:grid-cols-8 items-center">
                        <input type="text" name="name" value="{{ $channel->name }}" class="input-field h-10" {{ $reserved ? 'readonly' : '' }}>
                        <input type="text" name="slug" value="{{ $channel->slug }}" class="input-field h-10" {{ $reserved ? 'readonly' : '' }}>
                        <input type="text" name="description" value="{{ $channel->description }}" class="input-field h-10 lg:col-span-2" {{ $reserved ? 'readonly' : '' }}>
                        <input type="text" name="icon" value="{{ $channel->icon }}" class="input-field h-10 text-center" {{ $reserved ? 'readonly' : '' }}>
                        <input type="text" name="accent_color" value="{{ $channel->accent_color }}" class="input-field h-10" {{ $reserved ? 'readonly' : '' }}>
                        <input type="number" name="sort_order" value="{{ $channel->sort_order }}" class="input-field h-10" {{ $reserved ? 'readonly' : '' }}>
                        <div class="flex items-center justify-end gap-2">
                            @if($reserved)
                                <span class="text-sm font-medium text-gray-400">系统保留</span>
                            @else
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
                            @endif
                        </div>
                    </div>
                </form>
                @if(! $reserved)
                    <form id="delete-channel-{{ $channel->id }}" action="{{ route('admin.channels.destroy', $channel) }}" method="POST" class="hidden" onsubmit="return confirm('确认删除该频道？文章将自动归入未分类。')">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </div>
        @endforeach
    </section>
@endsection
