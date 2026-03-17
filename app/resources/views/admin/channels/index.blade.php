@extends('layouts.app')

@section('content')
    <section class="channel-admin-panel rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">频道管理</h2>
                <p class="mt-1 text-sm text-gray-500">拖拽卡片即可调整顺序；系统频道仅支持顶栏显示切换。</p>
            </div>
            <div class="icon-action-group">
                <x-icon-button :href="route('admin.articles.index')" icon="document" label="文章管理" title="文章管理" />
                <x-icon-button :href="route('admin.comments.index')" icon="chat-bubble-left-right" label="评论管理" title="评论管理" />
                <x-icon-button :href="route('admin.users.index')" icon="users" label="用户管理" title="用户管理" />
                <x-icon-button :href="route('admin.tags.index')" icon="tag" label="标签管理" title="标签管理" />
            </div>
        </div>

        <form action="{{ route('admin.channels.reorder') }}" method="POST" class="channel-admin-reorder-bar mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
            @csrf
            <div class="flex flex-wrap items-center gap-2">
                <span class="channel-admin-reorder-icon inline-flex h-7 w-7 items-center justify-center rounded-full bg-white text-lg">↕️</span>
                <span>拖拽频道卡片调整顺序</span>
            </div>
            <input type="hidden" name="ordered_ids" id="channel-order-input" value="">
            <button type="submit" class="btn-secondary">保存排序</button>
        </form>

        <form action="{{ route('admin.channels.store') }}" method="POST" class="channel-admin-create-form mt-6 rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-6 shadow-sm">
            @csrf
            <h3 class="mb-5 text-lg font-semibold text-gray-900">新增频道</h3>

            <div class="space-y-4">
                <!-- 基础信息 -->
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">频道名称 <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="input-field h-11" placeholder="例如：技术分享" required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">URL Slug</label>
                        <input type="text" name="slug" class="input-field h-11" placeholder="留空自动生成">
                    </div>
                </div>

                <!-- 描述 -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">频道简介</label>
                    <input type="text" name="description" class="input-field h-11" placeholder="简要描述频道的主题和内容">
                </div>

                <!-- 视觉元素 -->
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">图标 <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="icon" id="new-channel-icon" class="input-field h-11 pl-12" placeholder="📌" value="#" required>
                            <div class="channel-admin-preview-chip absolute left-3 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg bg-gray-100 text-xl" id="new-icon-preview">#</div>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">主题色 <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="accent_color" id="new-channel-color" class="input-field h-11 pl-12" placeholder="#8b5cf6" value="#8b5cf6" required pattern="^#[0-9A-Fa-f]{6}$">
                            <div class="channel-admin-color-preview absolute left-3 top-1/2 h-7 w-7 -translate-y-1/2 rounded-lg border-2 border-white shadow-sm" id="new-color-preview" style="background-color: #8b5cf6;"></div>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">排序权重</label>
                        <input type="number" name="sort_order" class="input-field h-11" placeholder="0" value="0" min="0" max="999">
                    </div>
                </div>

                <!-- 显示选项 -->
                <div class="channel-admin-toggle-card rounded-lg border border-gray-200 bg-white p-4">
                    <input type="hidden" name="show_in_top_nav" value="0">
                    <label class="inline-flex cursor-pointer items-center gap-3">
                        <input type="checkbox" name="show_in_top_nav" value="1" checked class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <span class="text-sm font-medium text-gray-700">在顶部导航栏显示该频道</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-primary mt-6 shadow-sm hover:shadow-md">新增频道</button>
        </form>

        <div class="channel-admin-system-note mt-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            系统频道：精华默认展示并聚合精华内容，未分类默认隐藏但仍会自动接收迁移文章。
        </div>
    </section>

    <section class="mt-6 space-y-3" id="channel-sort-list">
        @forelse($channels as $channel)
            @php($isReserved = $channel->isReserved())
            <div class="channel-admin-card article-card group flex items-start gap-3" data-channel-id="{{ $channel->id }}" draggable="true">
                <button type="button" class="channel-admin-drag-handle mt-1 cursor-grab rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm text-gray-400 hover:text-gray-600" title="拖拽排序" aria-label="拖拽排序">
                    ⠿
                </button>
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
                        <div class="channel-admin-inline-toggle flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                            <input type="hidden" name="show_in_top_nav" value="0">
                            <span class="text-xs text-gray-500">顶栏</span>
                            <label class="channel-admin-switch relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" name="show_in_top_nav" value="1" @checked($channel->show_in_top_nav) class="peer sr-only">
                                <div class="channel-admin-switch-track"></div>
                            </label>
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
            <section class="channel-admin-empty-state rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                当前只有系统保留频道，先新增一个可管理频道吧。
            </section>
        @endforelse
    </section>

    <script>
        const list = document.getElementById('channel-sort-list');
        const orderInput = document.getElementById('channel-order-input');
        let dragging = null;

        if (list && orderInput) {
            const updateOrder = () => {
                const ids = Array.from(list.querySelectorAll('[data-channel-id]'))
                    .map((item) => item.getAttribute('data-channel-id'));
                orderInput.value = ids.join(',');
            };

            updateOrder();

            list.addEventListener('dragstart', (event) => {
                const target = event.target.closest('[data-channel-id]');
                if (!target) return;
                dragging = target;
                target.classList.add('channel-admin-card-dragging');
                event.dataTransfer.effectAllowed = 'move';
            });

            list.addEventListener('dragend', () => {
                if (dragging) {
                    dragging.classList.remove('channel-admin-card-dragging');
                }
                dragging = null;
                updateOrder();
            });

            list.addEventListener('dragover', (event) => {
                event.preventDefault();
                const target = event.target.closest('[data-channel-id]');
                if (!target || !dragging || target === dragging) return;
                const rect = target.getBoundingClientRect();
                const before = event.clientY < rect.top + rect.height / 2;
                list.insertBefore(dragging, before ? target : target.nextSibling);
            });
        }

        // 实时预览：新增频道表单
        const newIconInput = document.getElementById('new-channel-icon');
        const newIconPreview = document.getElementById('new-icon-preview');
        const newColorInput = document.getElementById('new-channel-color');
        const newColorPreview = document.getElementById('new-color-preview');

        if (newIconInput && newIconPreview) {
            newIconInput.addEventListener('input', (e) => {
                newIconPreview.textContent = e.target.value || '#';
            });
        }

        if (newColorInput && newColorPreview) {
            newColorInput.addEventListener('input', (e) => {
                const color = e.target.value;
                if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                    newColorPreview.style.backgroundColor = color;
                }
            });
        }
    </script>
@endsection
