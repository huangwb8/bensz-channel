@extends('layouts.app')

@section('content')
    <section class="panel p-6 lg:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-violet-300/80">管理员</p>
                <h2 class="mt-3 text-3xl font-semibold">频道管理</h2>
            </div>
            <a href="{{ route('admin.articles.index') }}" class="btn-secondary">文章管理</a>
        </div>

        <form action="{{ route('admin.channels.store') }}" method="POST" class="mt-8 grid gap-4 rounded-3xl border border-white/8 bg-white/[0.03] p-5 lg:grid-cols-2">
            @csrf
            <input type="text" name="name" class="input-area h-12" placeholder="频道名称" required>
            <input type="text" name="slug" class="input-area h-12" placeholder="slug（留空自动生成）">
            <input type="text" name="description" class="input-area h-12 lg:col-span-2" placeholder="频道简介">
            <input type="text" name="icon" class="input-area h-12" placeholder="图标，例如 📢" value="#">
            <input type="text" name="accent_color" class="input-area h-12" placeholder="#8b5cf6" value="#8b5cf6">
            <input type="number" name="sort_order" class="input-area h-12" placeholder="排序" value="0">
            <div class="lg:col-span-2">
                <button type="submit" class="btn-primary">新增频道</button>
            </div>
        </form>
    </section>

    <section class="mt-6 space-y-4">
        @foreach($channels as $channel)
            <form action="{{ route('admin.channels.update', $channel) }}" method="POST" class="panel grid gap-4 p-5 lg:grid-cols-[1fr_1fr_2fr_120px_140px_120px]">
                @csrf
                @method('PUT')
                <input type="text" name="name" value="{{ $channel->name }}" class="input-area h-12">
                <input type="text" name="slug" value="{{ $channel->slug }}" class="input-area h-12">
                <input type="text" name="description" value="{{ $channel->description }}" class="input-area h-12">
                <input type="text" name="icon" value="{{ $channel->icon }}" class="input-area h-12">
                <input type="text" name="accent_color" value="{{ $channel->accent_color }}" class="input-area h-12">
                <div class="flex gap-3">
                    <input type="number" name="sort_order" value="{{ $channel->sort_order }}" class="input-area h-12 w-full">
                    <button type="submit" class="btn-secondary whitespace-nowrap">保存</button>
                </div>
            </form>
        @endforeach
    </section>
@endsection
