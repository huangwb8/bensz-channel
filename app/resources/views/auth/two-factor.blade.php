@extends('layouts.auth')

@section('content')
    <div class="auth-wrap">
        <div class="orb orb-1" aria-hidden="true"></div>
        <div class="orb orb-2" aria-hidden="true"></div>
        <div class="orb orb-3" aria-hidden="true"></div>

        <div class="login-card max-w-xl">
            <header class="card-header">
                <a href="{{ route('home') }}" class="brand-mark" title="返回主页" aria-label="返回主页">
                    <span aria-hidden="true">🔐</span>
                    <span class="brand-text">{{ $siteName ?? 'Bensz Channel' }}</span>
                </a>
                <h1 class="page-title">两步验证</h1>
                <p class="page-subtitle">主登录已通过，请输入验证器中的 6 位动态验证码，或使用恢复码完成登录。</p>
            </header>

            @if ($errors->any())
                <div class="msg-error" role="alert" aria-live="assertive">
                    <div>
                        <p class="msg-title">验证失败</p>
                        <ul class="msg-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('auth.two-factor.verify') }}" method="POST" class="mt-6 space-y-5">
                @csrf

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-slate-700">动态验证码</span>
                    <input type="text" name="code" class="input-field input-otp" inputmode="numeric" autocomplete="one-time-code" placeholder="输入 6 位动态码">
                </label>

                <div class="relative py-1 text-center text-xs uppercase tracking-[0.25em] text-slate-400">
                    <span class="bg-white px-3">或使用恢复码</span>
                </div>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-slate-700">恢复码</span>
                    <input type="text" name="recovery_code" class="input-field" autocomplete="off" placeholder="例如 ABCD-EFGH">
                </label>

                <button type="submit" class="btn-primary w-full justify-center">
                    验证并登录
                </button>
            </form>
        </div>
    </div>
@endsection
