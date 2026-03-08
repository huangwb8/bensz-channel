@extends('layouts.app')

@section('content')
    @php($currentUser = auth()->user())

    <section class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">账户设置</h1>
                    <p class="mt-1 text-sm text-gray-500">在这里维护你的基础资料、登录标识与密码登录能力；修改公开资料后会同步刷新静态页面。</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full px-3 py-1 font-semibold {{ $currentUser?->isAdmin() ? 'bg-violet-100 text-violet-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $currentUser?->isAdmin() ? '管理员账号' : '成员账号' }}
                    </span>
                    <span class="rounded-full px-3 py-1 font-semibold {{ $currentUser?->email_verified_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        邮箱{{ $currentUser?->email_verified_at ? '已验证' : '未验证' }}
                    </span>
                    <span class="rounded-full px-3 py-1 font-semibold {{ $currentUser?->phone_verified_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        手机{{ $currentUser?->phone_verified_at ? '已验证' : '未验证' }}
                    </span>
                </div>
            </div>

            <form action="{{ route('settings.account.profile.update') }}" method="POST" class="mt-6 space-y-6">
                @csrf
                @method('PUT')

                <section class="space-y-4">
                    <div>
                        <h2 class="text-base font-medium text-gray-900">基本资料</h2>
                        <p class="mt-1 text-sm text-gray-500">邮箱与手机号至少保留一个，避免失去登录标识。变更联系方式后会重新进入“未验证”状态。</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-gray-700">昵称</span>
                            <input type="text" name="name" value="{{ old('name', $currentUser?->name) }}" class="input-field" maxlength="40" required>
                        </label>
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-gray-700">头像链接</span>
                            <input type="url" name="avatar_url" value="{{ old('avatar_url', $currentUser?->avatar_url) }}" class="input-field" maxlength="2048" placeholder="https://example.com/avatar.png">
                        </label>
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-gray-700">邮箱</span>
                            <input type="email" name="email" value="{{ old('email', $currentUser?->email) }}" class="input-field" maxlength="120" placeholder="name@example.com">
                        </label>
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-gray-700">手机号</span>
                            <input type="text" name="phone" value="{{ old('phone', $currentUser?->phone) }}" class="input-field" maxlength="32" placeholder="13800000000">
                        </label>
                    </div>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-gray-700">个人简介</span>
                        <textarea name="bio" rows="4" class="input-field min-h-28 resize-y" maxlength="500" placeholder="介绍一下你自己、负责方向或兴趣领域">{{ old('bio', $currentUser?->bio) }}</textarea>
                    </label>
                </section>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        保存基本资料
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">密码设置</h2>
                    <p class="mt-1 text-sm text-gray-500">邮箱密码登录依赖邮箱标识。若当前账号尚未绑定邮箱，请先在上方资料区域填写邮箱。</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full px-3 py-1 font-semibold {{ $userHasPassword ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $userHasPassword ? '已设置密码' : '未设置密码' }}
                    </span>
                    <span class="rounded-full px-3 py-1 font-semibold {{ $passwordLoginEnabled ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $passwordLoginEnabled ? '站点已开启邮箱密码登录' : '站点当前未开放邮箱密码登录' }}
                    </span>
                </div>
            </div>

            @if(blank($currentUser?->email))
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    当前账号还没有绑定邮箱，因此暂时无法设置邮箱密码登录。
                </div>
            @endif

            <form action="{{ route('settings.account.password.update') }}" method="POST" class="mt-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-4 md:grid-cols-2">
                    @if($userHasPassword)
                        <label class="block space-y-2 md:col-span-2">
                            <span class="text-sm font-medium text-gray-700">当前密码</span>
                            <input type="password" name="current_password" class="input-field" autocomplete="current-password">
                        </label>
                    @endif

                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-gray-700">新密码</span>
                        <input type="password" name="password" class="input-field" autocomplete="new-password" minlength="8">
                    </label>
                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-gray-700">确认新密码</span>
                        <input type="password" name="password_confirmation" class="input-field" autocomplete="new-password" minlength="8">
                    </label>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                        {{ $userHasPassword ? '更新密码' : '设置密码' }}
                    </button>
                </div>
            </form>
        </section>
    </section>
@endsection
