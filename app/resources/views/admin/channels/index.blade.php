@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">频道管理</h2>
                <p class="mt-1 text-sm text-gray-500">管理社区的所有频道</p>
            </div>
            <a href="{{ route('admin.articles.index') }}" class="btn-secondary">文章管理</a>
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
            <form action="{{ route('admin.channels.update', $channel) }}" method="POST" class="article-card">
                @csrf
                @method('PUT')
                <div class="grid gap-4 lg:grid-cols-8 items-center">
                    <input type="text" name="name" value="{{ $channel->name }}" class="input-field h-10">
                    <input type="text" name="slug" value="{{ $channel->slug }}" class="input-field h-10">
                    <input type="text" name="description" value="{{ $channel->description }}" class="input-field h-10 lg:col-span-2">
                    <input type="text" name="icon" value="{{ $channel->icon }}" class="input-field h-10 text-center">
                    <input type="text" name="accent_color" value="{{ $channel->accent_color }}" class="input-field h-10">
                    <input type="number" name="sort_order" value="{{ $channel->sort_order }}" class="input-field h-10">
                    <button type="submit" class="btn-primary h-10">保存</button>
                </div>
            </form>
        @endforeach
    </section>
@endsection
