@extends('layouts.auth')

@section('content')
    @php($prefillChannel = old('channel', old('otp_channel', 'email')))
    @php($prefillTarget = old('target', old('otp_target')))

    <div class="auth-wrap">
        {{-- Animated background orbs --}}
        <div class="orb orb-1" aria-hidden="true"></div>
        <div class="orb orb-2" aria-hidden="true"></div>

        {{-- Dev OTP preview banner --}}
        @if (session('otp_preview'))
            <div class="dev-banner" role="note">
                <span>⚡ 开发验证码预览</span>
                <code>{{ session('otp_preview') }}</code>
            </div>
        @endif

        {{-- Login card --}}
        <div class="login-card">

            {{-- Brand header --}}
            <header class="card-header">
                <a href="{{ route('home') }}" class="brand-mark" aria-label="返回主页">
                    <span aria-hidden="true">💬</span>
                    <span class="brand-text">Bensz Channel</span>
                </a>
                <h1 class="page-title">欢迎回来</h1>
                <p class="page-subtitle">登录以继续访问社区内容</p>
            </header>

            {{-- Error alert --}}
            @if ($errors->any())
                <div class="msg-error" role="alert" aria-live="assertive">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="msg-title">提交失败</p>
                        <ul class="msg-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Status message --}}
            @if (session('status'))
                <div class="msg-ok" role="status" aria-live="polite">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            {{-- Step indicator --}}
            <div class="steps" aria-label="登录步骤">
                <div class="step">
                    <span class="step-dot">1</span>
                    <span>发送验证码</span>
                </div>
                <div class="step-connector" aria-hidden="true"></div>
                <div class="step">
                    <span class="step-dot">2</span>
                    <span>验证登录</span>
                </div>
            </div>

            {{-- Step 1: Send code --}}
            <form action="{{ route('auth.code.send') }}" method="POST" class="form-block" novalidate>
                @csrf
                <div class="field">
                    <label for="send-channel">接收方式</label>
                    <select id="send-channel" name="channel" class="input-field">
                        <option value="email" @selected($prefillChannel === 'email')>邮箱地址</option>
                        <option value="phone" @selected($prefillChannel === 'phone')>手机号码</option>
                    </select>
                </div>
                <div class="field">
                    <label for="send-target">邮箱 / 手机号</label>
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
                <button type="submit" class="btn-send">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    发送验证码
                </button>
            </form>

            {{-- Divider --}}
            <div class="divider" aria-hidden="true"><span>然后</span></div>

            {{-- Step 2: Verify code --}}
            <form action="{{ route('auth.code.verify') }}" method="POST" class="form-block" novalidate>
                @csrf
                <input type="hidden" name="channel" value="{{ $prefillChannel }}">
                <input type="hidden" name="target" value="{{ $prefillTarget }}">
                <div class="field">
                    <label for="verify-code">验证码</label>
                    <input
                        id="verify-code"
                        type="text"
                        name="code"
                        value="{{ old('code') }}"
                        class="input-field input-otp"
                        placeholder="000000"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                    >
                </div>
                <div class="field">
                    <label for="verify-name">
                        昵称
                        <span class="label-hint">（首次登录可选）</span>
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
                <button type="submit" class="btn-verify">
                    验证并登录
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </form>

            {{-- Social login --}}
            @if(!empty($providers))
                <div class="divider" aria-hidden="true"><span>或使用</span></div>
                <div class="social-row">
                    @foreach ($providers as $provider)
                        <form action="{{ route('auth.qr.start', $provider) }}" method="POST" style="display:contents">
                            @csrf
                            <button type="submit" class="btn-social btn-social-{{ $provider }}">
                                @if($provider === 'wechat')
                                    <svg aria-hidden="true" class="social-svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.582.582 0 0 1-.023-.156.49.49 0 0 1 .201-.398C23.024 18.48 24 16.82 24 14.98c0-3.21-2.931-5.837-6.656-6.088V8.89c-.135-.01-.269-.03-.406-.03zm-2.53 3.274c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.97-.982zm4.844 0c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982z"/>
                                    </svg>
                                    微信扫码
                                @else
                                    <svg aria-hidden="true" class="social-svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.003 2c-2.265 0-6.29 1.364-6.29 7.325v1.195S3.55 14.96 3.55 17.474c0 .665.17 1.025.281 1.025.114 0 .902-.484 1.748-2.072 0 0-.18 2.197 1.904 3.967 0 0-1.77.495-1.77 1.182 0 .686 4.078.43 6.29.43 2.213 0 6.29.256 6.29-.43 0-.687-1.77-1.182-1.77-1.182 2.085-1.77 1.905-3.967 1.905-3.967.846 1.588 1.634 2.072 1.748 2.072.11 0 .281-.36.281-1.025 0-2.514-2.164-6.954-2.164-6.954V9.325C18.29 3.364 14.268 2 12.003 2z"/>
                                    </svg>
                                    QQ 扫码
                                @endif
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif

            {{-- Footer --}}
            <p class="card-footer">登录即表示同意我们的服务条款与隐私政策</p>

        </div>{{-- /.login-card --}}
    </div>{{-- /.auth-wrap --}}
@endsection

@push('styles')
<style>
/* ===========================================
   Bensz Channel — Auth Page  (Midnight Community)
   =========================================== */

*, *::before, *::after { box-sizing: border-box; }

/* Page wrapper */
.auth-wrap {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    background-color: #06091a;
    background-image:
        radial-gradient(ellipse 65% 55% at 10% 15%, rgba(99,102,241,.22) 0%, transparent 55%),
        radial-gradient(ellipse 50% 45% at 88% 82%, rgba(52,211,153,.15) 0%, transparent 55%),
        radial-gradient(rgba(148,163,184,.035) 1px, transparent 1px);
    background-size: auto, auto, 28px 28px;
    position: relative;
    overflow: hidden;
    font-family: 'DM Sans', 'Plus Jakarta Sans', system-ui, sans-serif;
}

/* Ambient animated orbs */
.orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    will-change: transform;
}
.orb-1 {
    width: 650px; height: 650px;
    top: -20%; left: -12%;
    background: radial-gradient(circle, rgba(99,102,241,.22) 0%, transparent 65%);
    filter: blur(65px);
    animation: orbDrift1 14s ease-in-out infinite alternate;
}
.orb-2 {
    width: 480px; height: 480px;
    bottom: -12%; right: -8%;
    background: radial-gradient(circle, rgba(52,211,153,.18) 0%, transparent 65%);
    filter: blur(65px);
    animation: orbDrift2 18s ease-in-out infinite alternate;
}
@keyframes orbDrift1 {
    from { transform: translate(0,0) scale(1); }
    to   { transform: translate(45px,55px) scale(1.07); }
}
@keyframes orbDrift2 {
    from { transform: translate(0,0) scale(1); }
    to   { transform: translate(-55px,-40px) scale(1.09); }
}

/* Dev banner */
.dev-banner {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 200;
    background: linear-gradient(90deg, #f59e0b, #d97706);
    color: #1c1917;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .875rem;
    padding: .5rem 1.5rem;
    font-size: .8125rem;
    font-weight: 600;
    box-shadow: 0 2px 10px rgba(245,158,11,.45);
}
.dev-banner code {
    font-family: 'Courier New', monospace;
    font-size: .9375rem;
    letter-spacing: .35em;
    background: rgba(0,0,0,.12);
    padding: .125rem .625rem;
    border-radius: 4px;
}

/* Card */
.login-card {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 440px;
    background: #ffffff;
    border-radius: 24px;
    padding: 2.5rem 2.5rem 2rem;
    box-shadow:
        0 0 0 1px rgba(255,255,255,.05),
        0 32px 72px -12px rgba(0,0,0,.6),
        inset 0 0 0 1px rgba(0,0,0,.035);
    animation: cardIn .65s cubic-bezier(.16,1,.3,1) both;
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(28px) scale(.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Card header */
.card-header {
    text-align: center;
    margin-bottom: 2rem;
}
.brand-mark {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    text-decoration: none;
    margin-bottom: 1.25rem;
    transition: opacity .15s ease;
}
.brand-mark:hover { opacity: .7; }
.brand-mark > span:first-child { font-size: 1.75rem; line-height: 1; }
.brand-text {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.0625rem;
    color: #111827;
    letter-spacing: -.03em;
}
.page-title {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 1.875rem;
    color: #0f172a;
    letter-spacing: -.04em;
    line-height: 1.15;
    margin: 0 0 .5rem;
}
.page-subtitle {
    font-size: .875rem;
    color: #64748b;
    line-height: 1.55;
    margin: 0;
}

/* Alert messages */
.msg-error,
.msg-ok {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    border-radius: 12px;
    padding: .875rem 1rem;
    margin-bottom: 1.25rem;
    font-size: .8125rem;
    line-height: 1.55;
}
.msg-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
.msg-ok {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}
.msg-error svg, .msg-ok svg {
    width: 1.125rem; height: 1.125rem;
    flex-shrink: 0; margin-top: .1rem;
}
.msg-error svg { color: #dc2626; }
.msg-ok svg   { color: #16a34a; }
.msg-title { font-weight: 600; margin-bottom: .25rem; color: #7f1d1d; }
.msg-list  { list-style: none; padding: 0; margin: 0; }
.msg-list li + li { margin-top: .2rem; }

/* Steps */
.steps {
    display: flex;
    align-items: center;
    margin-bottom: 1.75rem;
}
.step {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .775rem;
    color: #94a3b8;
    font-weight: 500;
    white-space: nowrap;
}
.step-dot {
    width: 1.625rem; height: 1.625rem;
    border-radius: 50%;
    background: #f1f5f9;
    border: 1.5px solid #e2e8f0;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .6875rem;
    font-weight: 700;
    font-family: 'Syne', sans-serif;
    flex-shrink: 0;
}
.step-connector {
    flex: 1;
    height: 1px;
    background: #e2e8f0;
    margin: 0 .75rem;
}

/* Form */
.form-block {
    display: flex;
    flex-direction: column;
    gap: .875rem;
}
.field {
    display: flex;
    flex-direction: column;
    gap: .3rem;
}
.field > label {
    font-size: .8rem;
    font-weight: 600;
    color: #374151;
    letter-spacing: .01em;
    line-height: 1.4;
}
.label-hint {
    font-weight: 400;
    color: #9ca3af;
    font-size: .75rem;
}

/* Input overrides for auth card */
.login-card .input-field {
    background: #f8fafc;
    border-color: #e2e8f0;
    border-radius: 12px;
    padding-top: .8125rem;
    padding-bottom: .8125rem;
    font-size: .875rem;
    transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
}
.login-card .input-field:hover:not(:focus) {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
.login-card .input-field:focus {
    background: #ffffff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.14);
    outline: none;
}

/* OTP input */
.input-otp {
    text-align: center !important;
    font-size: 1.625rem !important;
    letter-spacing: .55em !important;
    padding-left: .5rem !important;
    padding-right: .5rem !important;
    font-family: 'Courier New', monospace !important;
    font-weight: 700 !important;
    color: #0f172a !important;
}

/* Send code button */
.btn-send {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    width: 100%;
    padding: .875rem 1.25rem;
    background: #111827;
    color: #f9fafb;
    font-size: .875rem;
    font-weight: 600;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    letter-spacing: .01em;
    transition: background .15s ease, transform .15s ease, box-shadow .15s ease;
    margin-top: .125rem;
}
.btn-send:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(0,0,0,.22);
}
.btn-send:active { transform: translateY(0); box-shadow: none; }
.btn-send svg { width: 1rem; height: 1rem; }

/* Verify button */
.btn-verify {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    width: 100%;
    padding: .875rem 1.25rem;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #ffffff;
    font-size: .875rem;
    font-weight: 600;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    letter-spacing: .01em;
    box-shadow: 0 4px 14px rgba(99,102,241,.38);
    transition: background .15s ease, transform .15s ease, box-shadow .15s ease;
    margin-top: .125rem;
}
.btn-verify:hover {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(99,102,241,.48);
}
.btn-verify:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(99,102,241,.3);
}
.btn-verify svg { width: 1rem; height: 1rem; }

/* Divider */
.divider {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin: 1.375rem 0;
}
.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
}
.divider span {
    font-size: .72rem;
    color: #94a3b8;
    font-weight: 500;
    white-space: nowrap;
}

/* Social login */
.social-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: .625rem;
}
.social-svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; }
.btn-social {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    width: 100%;
    padding: .75rem 1rem;
    border-radius: 12px;
    font-size: .8125rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s ease, border-color .15s ease, transform .15s ease, box-shadow .15s ease;
}
.btn-social-wechat {
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    color: #15803d;
}
.btn-social-wechat:hover {
    background: #dcfce7;
    border-color: #86efac;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(34,197,94,.15);
}
.btn-social-qq {
    background: #eff6ff;
    border: 1.5px solid #bfdbfe;
    color: #1d4ed8;
}
.btn-social-qq:hover {
    background: #dbeafe;
    border-color: #93c5fd;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59,130,246,.15);
}

/* Card footer */
.card-footer {
    text-align: center;
    font-size: .72rem;
    color: #94a3b8;
    margin-top: 1.5rem;
    line-height: 1.6;
    padding-top: 1.25rem;
    border-top: 1px solid #f1f5f9;
}

/* Mobile */
@media (max-width: 480px) {
    .login-card {
        padding: 2rem 1.5rem 1.75rem;
        border-radius: 20px;
    }
    .page-title { font-size: 1.625rem; }
    .orb-1 { width: 320px; height: 320px; }
    .orb-2 { width: 240px; height: 240px; }
    .social-row { grid-template-columns: 1fr; }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .orb, .login-card { animation: none !important; }
    .btn-send, .btn-verify, .btn-social { transition: none !important; }
}
</style>
@endpush
