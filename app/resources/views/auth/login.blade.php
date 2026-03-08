@extends('layouts.app')

@section('content')
    <section class="grid gap-6 lg:grid-cols-2">
        <div class="panel p-6 lg:p-8">
            <p class="text-xs uppercase tracking-[0.35em] text-violet-300/80">登录 / 注册</p>
            <h2 class="mt-3 text-3xl font-semibold">邮箱或手机号验证码登录</h2>
            <p class="mt-4 text-sm leading-7 text-slate-300">无需密码，输入验证码即可创建或登录成员账号。</p>

            <div class="mt-8 grid gap-6">
                <form action="{{ route('auth.code.send') }}" method="POST" class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                    @csrf
                    <div class="grid gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">发送渠道</label>
                            <select name="channel" class="input-area h-12">
                                <option value="email">邮箱</option>
                                <option value="phone">手机号</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">邮箱 / 手机号</label>
                            <input type="text" name="target" value="{{ old('otp_target') }}" class="input-area h-12" placeholder="name@example.com 或 13800138000">
                        </div>
                        <button type="submit" class="btn-primary">发送验证码</button>
                    </div>
                </form>

                <form action="{{ route('auth.code.verify') }}" method="POST" class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                    @csrf
                    <div class="grid gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">验证渠道</label>
                            <select name="channel" class="input-area h-12">
                                <option value="email" @selected(old('otp_channel') === 'email')>邮箱</option>
                                <option value="phone" @selected(old('otp_channel') === 'phone')>手机号</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">邮箱 / 手机号</label>
                            <input type="text" name="target" value="{{ old('otp_target') }}" class="input-area h-12" placeholder="和上一步保持一致">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">验证码</label>
                            <input type="text" name="code" class="input-area h-12 tracking-[0.35em]" placeholder="6 位验证码">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">昵称（首次登录可选）</label>
                            <input type="text" name="name" class="input-area h-12" placeholder="例如：频道新人">
                        </div>
                        <button type="submit" class="btn-primary">验证并登录</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel p-6 lg:p-8">
            <p class="text-xs uppercase tracking-[0.35em] text-cyan-300/80">扫码登录</p>
            <h2 class="mt-3 text-3xl font-semibold">微信 / QQ 扫码演示</h2>
            <p class="mt-4 text-sm leading-7 text-slate-300">为了让本地 Docker 部署开箱即用，这里提供与正式第三方接入兼容的演示扫码流程。</p>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                @foreach($providers as $provider)
                    <form action="{{ route('auth.qr.start', $provider) }}" method="POST" class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                        @csrf
                        <div class="text-2xl">{{ $provider === 'wechat' ? '🟢' : '🔵' }}</div>
                        <h3 class="mt-3 text-lg font-semibold">{{ $provider === 'wechat' ? '微信扫码' : 'QQ 扫码' }}</h3>
                        <p class="mt-2 text-sm text-slate-400">生成一次性二维码，打开后完成模拟授权。</p>
                        <button type="submit" class="btn-secondary mt-5 w-full">开始扫码</button>
                    </form>
                @endforeach
            </div>

            <div class="mt-8 rounded-3xl border border-dashed border-white/10 p-5 text-sm leading-7 text-slate-300">
                <p>默认管理员：</p>
                <p class="mt-2 font-mono text-cyan-200">{{ config('community.admin.email') }} / {{ config('community.admin.password') }}</p>
                <p class="mt-4">示例成员：</p>
                <p class="mt-2 font-mono text-cyan-200">member@example.com / member123456</p>
            </div>
        </div>
    </section>
@endsection
