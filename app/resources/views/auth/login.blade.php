@extends('layouts.app')

@section('content')
    @php($prefillChannel = old('channel', old('otp_channel', 'email')))
    @php($prefillTarget = old('target', old('otp_target')))

    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
        <!-- 品牌标识 -->
        <div class="mb-12 text-center">
            <a href="{{ route('home') }}" class="inline-block text-sm font-semibold text-gray-500 hover:text-gray-900 transition-colors tracking-wide uppercase">
                Bensz Channel
            </a>
        </div>

        <!-- 主容器 -->
        <div class="w-full max-w-md space-y-8">
            <!-- 头部 -->
            <div class="text-center space-y-3">
                <h1 class="text-4xl font-bold text-gray-900 tracking-tight">
                    欢迎回来
                </h1>
                <p class="text-base text-gray-500 leading-relaxed">
                    登录以继续访问社区内容
                </p>
            </div>

            <!-- 错误提示 -->
            @if ($errors->any())
                <div class="rounded-2xl bg-red-50 border border-red-100 p-5 text-sm text-red-900" role="alert">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-semibold mb-2">提交失败</p>
                            <ul class="space-y-1 text-red-800">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 状态消息 -->
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-5 text-sm text-emerald-900" role="status">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="font-medium">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            <!-- 验证码登录 -->
            <div class="space-y-6">
                <!-- 第一步：发送验证码 -->
                <form action="{{ route('auth.code.send') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label for="send-channel" class="block text-sm font-medium text-gray-700 mb-2">
                            接收方式
                        </label>
                        <select id="send-channel" name="channel" class="input-field">
                            <option value="email" @selected($prefillChannel === 'email')>邮箱地址</option>
                            <option value="phone" @selected($prefillChannel === 'phone')>手机号码</option>
                        </select>
                    </div>

                    <div>
                        <label for="send-target" class="block text-sm font-medium text-gray-700 mb-2">
                            邮箱 / 手机号
                        </label>
                        <input
                            id="send-target"
                            type="text"
                            name="target"
                            value="{{ $prefillTarget }}"
                            class="input-field"
                            placeholder="name@example.com"
                            autocomplete="username"
                        >
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        发送验证码
                    </button>
                </form>

                <!-- 分隔线 -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-400">然后</span>
                    </div>
                </div>

                <!-- 第二步：验证登录 -->
                <form action="{{ route('auth.code.verify') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label for="verify-code" class="block text-sm font-medium text-gray-700 mb-2">
                            验证码
                        </label>
                        <input
                            id="verify-code"
                            type="text"
                            name="code"
                            value="{{ old('code') }}"
                            class="input-field text-center text-lg tracking-widest font-mono"
                            placeholder="000000"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                        >
                        <input type="hidden" name="channel" value="{{ $prefillChannel }}">
                        <input type="hidden" name="target" value="{{ $prefillTarget }}">
                    </div>

                    <div>
                        <label for="verify-name" class="block text-sm font-medium text-gray-700 mb-2">
                            昵称 <span class="text-gray-400 font-normal">(首次登录可选)</span>
                        </label>
                        <input
                            id="verify-name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="input-field"
                            placeholder="例如：频道新人"
                            autocomplete="nickname"
                            maxlength="40"
                        >
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        验证并登录
                    </button>
                </form>
            </div>

            <!-- 分隔线 -->
            <div class="relative py-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-4 bg-white text-xs text-gray-400 uppercase tracking-wider font-medium">
                        或使用
                    </span>
                </div>
            </div>

            <!-- 扫码登录 -->
            <div class="grid grid-cols-2 gap-3">
                @foreach ($providers as $provider)
                    <form action="{{ route('auth.qr.start', $provider) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex flex-col items-center justify-center gap-2 px-4 py-4 rounded-xl border border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50 transition-all group">
                            <span class="text-2xl {{ $provider === 'wechat' ? 'text-emerald-600' : 'text-blue-600' }}">
                                @if($provider === 'wechat')
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.582.582 0 0 1-.023-.156.49.49 0 0 1 .201-.398C23.024 18.48 24 16.82 24 14.98c0-3.21-2.931-5.837-6.656-6.088V8.89c-.135-.01-.269-.03-.406-.03zm-2.53 3.274c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.97-.982zm4.844 0c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982z"/>
                                    </svg>
                                @else
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.003 2c-2.265 0-6.29 1.364-6.29 7.325v1.195S3.55 14.96 3.55 17.474c0 .665.17 1.025.281 1.025.114 0 .902-.484 1.748-2.072 0 0-.18 2.197 1.904 3.967 0 0-1.77.495-1.77 1.182 0 .686 4.078.43 6.29.43 2.213 0 6.29.256 6.29-.43 0-.687-1.77-1.182-1.77-1.182 2.085-1.77 1.905-3.967 1.905-3.967.846 1.588 1.634 2.072 1.748 2.072.11 0 .281-.36.281-1.025 0-2.514-2.164-6.954-2.164-6.954V9.325C18.29 3.364 14.268 2 12.003 2z"/>
                                    </svg>
                                @endif
                            </span>
                            <span class="text-xs font-medium text-gray-600 group-hover:text-gray-900">
                                {{ $provider === 'wechat' ? '微信' : 'QQ' }}扫码
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>

            <!-- 底部说明 -->
            <div class="pt-6 text-center">
                <p class="text-xs text-gray-400 leading-relaxed">
                    登录即表示同意我们的服务条款与隐私政策
                </p>
            </div>
        </div>
    </div>
@endsection
