@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">频道管理</h2>
                <p class="mt-1 text-sm text-gray-500">管理社区频道，并决定哪些频道出现在顶栏导航中；系统保留频道包括精华与未分类。</p>
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
            <input type="hidden" name="show_in_top_nav" value="0">
            <label class="mt-4 inline-flex items-center gap-3 text-sm text-gray-600">
                <input type="checkbox" name="show_in_top_nav" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span>在顶栏显示该频道</span>
            </label>
            <button type="submit" class="btn-primary mt-4">新增频道</button>
        </form>

        <div class="mt-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            精华与未分类为系统保留频道：精华默认展示且聚合全站精华文章，未分类默认隐藏但仍会自动接收迁移文章。
        </div>
    </section>

    <section class="mt-6 space-y-3">
        @forelse($channels as $channel)
            @php($isReserved = $channel->isReserved())
            <div class="article-card">
                <form action="{{ route('admin.channels.update', $channel) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid items-center gap-4 lg:grid-cols-9">
                        <input type="text" name="name" value="{{ $channel->name }}" class="input-field h-10 {{ $isReserved ? 'bg-gray-100 text-gray-500' : '' }}" @disabled($isReserved)>
                        <input type="text" name="slug" value="{{ $channel->slug }}" class="input-field h-10 {{ $isReserved ? 'bg-gray-100 text-gray-500' : '' }}" @disabled($isReserved)>
                        <input type="text" name="description" value="{{ $channel->description }}" class="input-field h-10 lg:col-span-2 {{ $isReserved ? 'bg-gray-100 text-gray-500' : '' }}" @disabled($isReserved)>
                        <input type="text" name="icon" value="{{ $channel->icon }}" class="input-field h-10 text-center {{ $isReserved ? 'bg-gray-100 text-gray-500' : '' }}" @disabled($isReserved)>
                        <input type="text" name="accent_color" value="{{ $channel->accent_color }}" class="input-field h-10 {{ $isReserved ? 'bg-gray-100 text-gray-500' : '' }}" @disabled($isReserved)>
                        <input type="number" name="sort_order" value="{{ $channel->sort_order }}" class="input-field h-10 {{ $isReserved ? 'bg-gray-100 text-gray-500' : '' }}" @disabled($isReserved)>
                        <div class="space-y-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                            <input type="hidden" name="show_in_top_nav" value="0">
                            <label class="inline-flex items-center gap-2 font-medium text-gray-700">
                                <input type="checkbox" name="show_in_top_nav" value="1" @checked($channel->show_in_top_nav) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>顶栏显示</span>
                            </label>
                            <p class="text-xs text-gray-500">
                                @if($isReserved)
                                    系统保留频道，仅支持调整是否出现在顶栏。
                                @else
                                    关闭后频道仍可访问，只是不再出现在顶栏导航。
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <x-icon-button
                                icon="save"
                                label="保存频道"
                                title="保存频道"
                                :aria-label="'保存频道：'.$channel->name"
                                variant="primary"
                                type="submit"
                            />
                            @unless($isReserved)
                                <x-icon-button
                                    icon="trash"
                                    label="删除频道"
                                    title="删除频道"
                                    :aria-label="'删除频道：'.$channel->name"
                                    variant="danger"
                                    type="submit"
                                    form="delete-channel-{{ $channel->id }}"
                                />
                            @endunless
                        </div>
                    </div>
                </form>
                @unless($isReserved)
                    <form id="delete-channel-{{ $channel->id }}" action="{{ route('admin.channels.destroy', $channel) }}" method="POST" class="hidden" onsubmit="return confirm('确认删除该频道？文章将自动归入未分类。')">
                        @csrf
                        @method('DELETE')
                    </form>
                @endunless
            </div>
        @empty
            <section class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                当前只有系统保留频道，先新增一个可管理频道吧。
            </section>
        @endforelse
    </section>
@endsection
