@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <!-- 返回首页 -->
        <div class="mb-6">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                返回首页
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- 验证码登录 -->
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="text-xl font-semibold text-gray-900">邮箱或手机号登录</h2>
                <p class="mt-2 text-sm text-gray-500">无需密码，输入验证码即可创建或登录账号。</p>

                <div class="mt-6 space-y-5">
                    <form action="{{ route('auth.code.send') }}" method="POST" class="rounded-lg border border-gray-200 bg-gray-50 p-5">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">发送渠道</label>
                                <select name="channel" class="input-field h-11">
                                    <option value="email">邮箱</option>
                                    <option value="phone">手机号</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">邮箱 / 手机号</label>
                                <input type="text" name="target" value="{{ old('otp_target') }}" class="input-field h-11" placeholder="name@example.com 或 13800138000">
                            </div>
                            <button type="submit" class="btn-primary w-full">发送验证码</button>
                        </div>
                    </form>

                    <form action="{{ route('auth.code.verify') }}" method="POST" class="rounded-lg border border-gray-200 bg-gray-50 p-5">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">验证渠道</label>
                                <select name="channel" class="input-field h-11">
                                    <option value="email" @selected(old('otp_channel') === 'email')>邮箱</option>
                                    <option value="phone" @selected(old('otp_channel') === 'phone')>手机号</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">邮箱 / 手机号</label>
                                <input type="text" name="target" value="{{ old('otp_target') }}" class="input-field h-11" placeholder="和上一步保持一致">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">验证码</label>
                                <input type="text" name="code" class="input-field h-11 font-mono tracking-widest" placeholder="6 位验证码">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">昵称 <span class="text-gray-400">（首次登录可选）</span></label>
                                <input type="text" name="name" class="input-field h-11" placeholder="例如：频道新人">
                            </div>
                            <button type="submit" class="btn-primary w-full">验证并登录</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 扫码登录 -->
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="text-xl font-semibold text-gray-900">扫码登录</h2>
                <p class="mt-2 text-sm text-gray-500">演示微信 / QQ 扫码登录流程。</p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach($providers as $provider)
                        <form action="{{ route('auth.qr.start', $provider) }}" method="POST" class="rounded-lg border border-gray-200 bg-gray-50 p-5 text-center">
                            @csrf
                            <div class="text-3xl mb-2">{{ $provider === 'wechat' ? '🟢' : '🔵' }}</div>
                            <h3 class="font-medium text-gray-900">{{ $provider === 'wechat' ? '微信扫码' : 'QQ 扫码' }}</h3>
                            <p class="mt-1 text-xs text-gray-500">生成一次性二维码</p>
                            <button type="submit" class="btn-secondary mt-4 w-full">开始扫码</button>
                        </form>
                    @endforeach
                </div>

                <div class="mt-6 rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm">
                    <p class="font-medium text-amber-800 mb-2">测试账号</p>
                    <div class="space-y-2 text-amber-700">
                        <p>管理员：<code class="bg-amber-100 px-1 rounded">{{ config('community.admin.email') }}</code> / <code class="bg-amber-100 px-1 rounded">{{ config('community.admin.password') }}</code></p>
                        <p>成员：<code class="bg-amber-100 px-1 rounded">member@example.com</code> / <code class="bg-amber-100 px-1 rounded">member123456</code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
