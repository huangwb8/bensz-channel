@extends('layouts.app')

@section('content')
    @php($prefillChannel = old('channel', old('otp_channel', 'email')))
    @php($prefillTarget = old('target', old('otp_target')))

    <div class="auth-shell">
        <div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                返回首页
            </a>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.08fr_0.92fr]">
            <section class="auth-hero">
                <div class="auth-hero-glow"></div>
                <div class="auth-hero-glow-secondary"></div>

                <div class="relative z-10">
                    <span class="auth-kicker auth-kicker-dark">Bensz Channel · 安全登录</span>
                    <h1 class="mt-5 text-4xl font-semibold tracking-tight text-white sm:text-5xl">欢迎回来</h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-blue-50/85 sm:text-lg">
                        继续访问你的频道、帖子与互动消息。这里保留了验证码登录与扫码登录两条主路径，操作更直观，也更适合移动端与桌面端快速切换。
                    </p>

                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        <div class="auth-metric">
                            <div class="text-sm text-blue-100/70">登录方式</div>
                            <div class="mt-2 text-2xl font-semibold text-white">2 种</div>
                            <p class="mt-2 text-sm text-blue-50/80">验证码与扫码并行可用</p>
                        </div>
                        <div class="auth-metric">
                            <div class="text-sm text-blue-100/70">验证时效</div>
                            <div class="mt-2 text-2xl font-semibold text-white">10 分钟</div>
                            <p class="mt-2 text-sm text-blue-50/80">短时有效，减少误用风险</p>
                        </div>
                        <div class="auth-metric">
                            <div class="text-sm text-blue-100/70">使用体验</div>
                            <div class="mt-2 text-2xl font-semibold text-white">免密码</div>
                            <p class="mt-2 text-sm text-blue-50/80">更轻量，也更适合演示环境</p>
                        </div>
                    </div>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="auth-feature">
                            <div class="mt-0.5 text-xl">⚡</div>
                            <div>
                                <p class="font-semibold text-white">最快两步完成登录</p>
                                <p class="mt-1 text-sm leading-6 text-blue-50/80">先发送验证码，再直接在当前页完成校验；首次登录可顺手补一个昵称。</p>
                            </div>
                        </div>
                        <div class="auth-feature">
                            <div class="mt-0.5 text-xl">🛡️</div>
                            <div>
                                <p class="font-semibold text-white">更克制的凭据展示</p>
                                <p class="mt-1 text-sm leading-6 text-blue-50/80">登录页不再直接暴露演示凭据，降低审查环境中的误泄露与误操作概率。</p>
                            </div>
                        </div>
                        <div class="auth-feature">
                            <div class="mt-0.5 text-xl">📱</div>
                            <div>
                                <p class="font-semibold text-white">扫码链路更顺手</p>
                                <p class="mt-1 text-sm leading-6 text-blue-50/80">支持微信与 QQ 演示扫码流程，适合移动端辅助确认授权。</p>
                            </div>
                        </div>
                        <div class="auth-feature">
                            <div class="mt-0.5 text-xl">🧭</div>
                            <div>
                                <p class="font-semibold text-white">界面层级更清晰</p>
                                <p class="mt-1 text-sm leading-6 text-blue-50/80">关键信息、操作入口与说明分层呈现，减少首次使用时的理解负担。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="space-y-6">
                @if ($errors->any())
                    <div class="rounded-3xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800 shadow-sm">
                        <p class="font-semibold">提交失败，请检查以下信息</p>
                        <ul class="mt-3 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="auth-panel">
                    <div class="auth-section-header">
                        <div>
                            <span class="auth-kicker auth-kicker-light">首选方式</span>
                            <h2 class="mt-4 text-2xl font-semibold text-slate-900">邮箱或手机号登录</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-500">无需记忆密码。先发送验证码，再在下方完成验证并进入社区。</p>
                        </div>
                        <div class="auth-note">
                            验证码发送成功后，会自动保留你刚填写的渠道与账号信息。
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <form action="{{ route('auth.code.send') }}" method="POST" class="auth-section">
                            @csrf
                            <div class="grid gap-4 sm:grid-cols-[140px_1fr]">
                                <div>
                                    <label for="send-channel" class="mb-2 block text-sm font-medium text-slate-700">发送渠道</label>
                                    <select id="send-channel" name="channel" class="input-field h-11">
                                        <option value="email" @selected($prefillChannel === 'email')>邮箱</option>
                                        <option value="phone" @selected($prefillChannel === 'phone')>手机号</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="send-target" class="mb-2 block text-sm font-medium text-slate-700">邮箱 / 手机号</label>
                                    <input id="send-target" type="text" name="target" value="{{ $prefillTarget }}" class="input-field h-11" placeholder="name@example.com 或 13800138000" autocomplete="username">
                                </div>
                            </div>

                            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-slate-500">推荐先使用邮箱登录；若在移动端访问，也可以直接切到扫码入口。</p>
                                <button type="submit" class="btn-primary w-full sm:w-auto">发送验证码</button>
                            </div>
                        </form>

                        <form action="{{ route('auth.code.verify') }}" method="POST" class="auth-section">
                            @csrf
                            <div class="grid gap-4 sm:grid-cols-[140px_1fr]">
                                <div>
                                    <label for="verify-channel" class="mb-2 block text-sm font-medium text-slate-700">验证渠道</label>
                                    <select id="verify-channel" name="channel" class="input-field h-11">
                                        <option value="email" @selected($prefillChannel === 'email')>邮箱</option>
                                        <option value="phone" @selected($prefillChannel === 'phone')>手机号</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="verify-target" class="mb-2 block text-sm font-medium text-slate-700">邮箱 / 手机号</label>
                                    <input id="verify-target" type="text" name="target" value="{{ $prefillTarget }}" class="input-field h-11" placeholder="与上一步保持一致" autocomplete="username">
                                </div>
                            </div>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="verify-code" class="mb-2 block text-sm font-medium text-slate-700">验证码</label>
                                    <input id="verify-code" type="text" name="code" value="{{ old('code') }}" class="input-field h-11 font-mono tracking-[0.3em]" placeholder="6 位验证码" inputmode="numeric" autocomplete="one-time-code">
                                </div>
                                <div>
                                    <label for="verify-name" class="mb-2 block text-sm font-medium text-slate-700">昵称 <span class="text-slate-400">（首次登录可选）</span></label>
                                    <input id="verify-name" type="text" name="name" value="{{ old('name') }}" class="input-field h-11" placeholder="例如：频道新人" autocomplete="nickname">
                                </div>
                            </div>

                            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-slate-500">验证成功后会自动建立登录会话，并回到你原本要访问的页面。</p>
                                <button type="submit" class="btn-primary w-full sm:w-auto">验证并登录</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="auth-panel">
                    <div class="auth-section-header">
                        <div>
                            <span class="auth-kicker auth-kicker-light">快捷方式</span>
                            <h2 class="mt-4 text-2xl font-semibold text-slate-900">扫码登录</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-500">适合使用微信或 QQ 的移动端辅助确认授权，生成一次性二维码后即可继续。</p>
                        </div>
                        <div class="auth-note">
                            二维码为一次性会话，过期后请重新生成。
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        @foreach ($providers as $provider)
                            <form action="{{ route('auth.qr.start', $provider) }}" method="POST" class="auth-provider-card">
                                @csrf
                                <div class="auth-provider-icon {{ $provider === 'wechat' ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600' }}">
                                    {{ $provider === 'wechat' ? '微' : 'Q' }}
                                </div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $provider === 'wechat' ? '微信扫码' : 'QQ 扫码' }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">生成当前设备专属二维码，使用 {{ $provider === 'wechat' ? '微信' : 'QQ' }} 端完成确认授权。</p>
                                <div class="mt-5 flex items-center justify-between text-sm text-slate-400">
                                    <span>一次性二维码</span>
                                    <span>即时跳转</span>
                                </div>
                                <button type="submit" class="btn-secondary mt-5 w-full">开始扫码</button>
                            </form>
                        @endforeach
                    </div>

                    <div class="mt-6 auth-note">
                        若你正在桌面端访问社区，推荐直接使用扫码方式；若当前设备可收邮箱或短信验证码，则验证码登录通常更快。
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
