@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">用户管理</h2>
                <p class="mt-1 text-sm text-gray-500">查看社区成员、调整角色，并维护基础资料。</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.articles.index') }}" class="btn-secondary">文章管理</a>
                <a href="{{ route('admin.channels.index') }}" class="btn-secondary">频道管理</a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-500">总用户数</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-500">管理员</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['admins'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-500">成员</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['members'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-500">7 日活跃</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['recent'] }}</p>
            </div>
        </div>

        <form action="{{ route('admin.users.index') }}" method="GET" class="mt-6 grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-5 lg:grid-cols-[minmax(0,2fr)_180px_auto]">
            <input
                type="text"
                name="q"
                value="{{ $filters['q'] }}"
                class="input-field h-11"
                placeholder="搜索昵称、邮箱或手机号"
            >
            <select name="role_filter" class="input-field h-11">
                <option value="">全部角色</option>
                <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected($filters['role'] === \App\Models\User::ROLE_ADMIN)>管理员</option>
                <option value="{{ \App\Models\User::ROLE_MEMBER }}" @selected($filters['role'] === \App\Models\User::ROLE_MEMBER)>成员</option>
            </select>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">筛选</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">重置</a>
            </div>
        </form>
    </section>

    <section class="mt-6 space-y-4">
        @forelse($users as $managedUser)
            @php
                $isEditingRow = (int) old('editing_user_id', 0) === $managedUser->id;
            @endphp
            <form action="{{ route('admin.users.update', $managedUser) }}" method="POST" class="article-card">
                @csrf
                @method('PUT')
                <input type="hidden" name="editing_user_id" value="{{ $managedUser->id }}">
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                <input type="hidden" name="role_filter" value="{{ $filters['role'] }}">

                @if($isEditingRow && $errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        当前用户保存失败，请检查输入后重试。
                    </div>
                @endif

                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $managedUser->name }}</h3>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $managedUser->isAdmin() ? 'bg-violet-100 text-violet-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $managedUser->isAdmin() ? '管理员' : '成员' }}
                            </span>
                            @if(auth()->id() === $managedUser->id)
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">当前登录</span>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap gap-4 text-xs text-gray-400">
                            <span>发文 {{ $managedUser->articles_count }}</span>
                            <span>评论 {{ $managedUser->comments_count }}</span>
                            <span>最近活跃 {{ optional($managedUser->last_seen_at)->format('Y-m-d H:i') ?? '暂无记录' }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">昵称</label>
                        <input type="text" name="name" value="{{ $isEditingRow ? old('name', $managedUser->name) : $managedUser->name }}" class="input-field h-11" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">角色</label>
                        <select name="role" class="input-field h-11">
                            <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(($isEditingRow ? old('role', $managedUser->role) : $managedUser->role) === \App\Models\User::ROLE_ADMIN)>管理员</option>
                            <option value="{{ \App\Models\User::ROLE_MEMBER }}" @selected(($isEditingRow ? old('role', $managedUser->role) : $managedUser->role) === \App\Models\User::ROLE_MEMBER)>成员</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">邮箱</label>
                        <input type="email" name="email" value="{{ $isEditingRow ? old('email', $managedUser->email) : $managedUser->email }}" class="input-field h-11" placeholder="member@example.com">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">手机号</label>
                        <input type="text" name="phone" value="{{ $isEditingRow ? old('phone', $managedUser->phone) : $managedUser->phone }}" class="input-field h-11" placeholder="13800138000">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">简介</label>
                    <textarea name="bio" rows="3" class="input-field text-sm" placeholder="介绍该用户在社区中的职责或背景">{{ $isEditingRow ? old('bio', $managedUser->bio) : $managedUser->bio }}</textarea>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs text-gray-400">
                        <span>邮箱{{ $managedUser->email_verified_at ? '已验证' : '未验证' }}</span>
                        <span class="mx-2">·</span>
                        <span>手机{{ $managedUser->phone_verified_at ? '已验证' : '未验证' }}</span>
                    </div>
                    <button type="submit" class="btn-primary">保存用户</button>
                </div>
            </form>
        @empty
            <section class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                当前筛选条件下没有用户。
            </section>
        @endforelse

        @if($users->hasPages())
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </section>
@endsection
