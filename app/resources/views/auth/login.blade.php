@extends('layouts.auth')

@section('content')
    @php
        $emailCodeTarget = old('target', old('otp_target'));
        $methodCatalog = [
            'email_code' => [
                'id' => 'email-code',
                'label' => '邮箱 + 验证码',
                'summary' => '适合首次登录与临时设备',
            ],
            'email_password' => [
                'id' => 'email-password',
                'label' => '邮箱 + 密码',
                'summary' => '适合常用设备快速登录',
            ],
            'wechat_qr' => [
                'id' => 'wechat',
                'label' => '微信扫码',
                'summary' => '使用微信确认登录',
            ],
            'qq_qr' => [
                'id' => 'qq',
                'label' => 'QQ扫码',
                'summary' => '使用 QQ 客户端扫码',
            ],
        ];

        $enabledMethodKeys = array_values(array_filter((array) ($enabledAuthMethods ?? []), fn ($method) => is_string($method) && array_key_exists($method, $methodCatalog)));
        $methods = array_map(fn ($methodKey) => $methodCatalog[$methodKey], $enabledMethodKeys);
        $methodIds = array_map(fn ($method) => $method['id'], $methods);
        $activeMethod = old('login_method', $methodIds[0] ?? null);

        if (! in_array($activeMethod, $methodIds, true)) {
            $activeMethod = $methodIds[0] ?? null;
        }

        $providerEnabled = [
            'wechat' => in_array('wechat', $providers ?? [], true),
            'qq' => in_array('qq', $providers ?? [], true),
        ];
        $providerMeta = [
            'wechat' => $socialProviders['wechat'] ?? ['mode' => 'demo', 'action_label' => '生成微信演示二维码', 'helper_text' => '当前为内置演示模式。', 'available' => true],
            'qq' => $socialProviders['qq'] ?? ['mode' => 'demo', 'action_label' => '生成 QQ 演示二维码', 'helper_text' => '当前为内置演示模式。', 'available' => true],
        ];
    @endphp

    <div class="auth-wrap">
        <div class="orb orb-1" aria-hidden="true"></div>
        <div class="orb orb-2" aria-hidden="true"></div>

        @if (session('otp_preview'))
            <div class="dev-banner" role="note">
                <span>⚡ 开发验证码预览</span>
                <code>{{ session('otp_preview') }}</code>
            </div>
        @endif

        <div class="login-card">
            <header class="card-header">
                <a href="{{ route('home') }}" class="brand-mark" aria-label="返回主页">
                    <span aria-hidden="true">💬</span>
                    <span class="brand-text">{{ $siteName ?? 'Bensz Channel' }}</span>
                </a>
                <p class="auth-badge">Better Auth 驱动的自托管安全登录</p>
                <h1 class="page-title">欢迎回来</h1>
                <p class="page-subtitle">选择一种登录方式继续访问社区内容</p>
            </header>

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

            @if (session('status'))
                <div class="msg-ok" role="status" aria-live="polite">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            <div class="section-kicker">选择登录方式</div>
            <div class="method-switcher">
                @if ($methods === [])
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                        当前暂未开放任何登录 / 注册方式，请联系管理员处理后台配置。
                    </div>
                @else
                    @foreach ($methods as $method)
                        <input
                            id="method-{{ $method['id'] }}"
                            class="method-toggle"
                            type="radio"
                            name="auth_method_switcher"
                            @checked($activeMethod === $method['id'])
                        >
                    @endforeach

                    <div class="method-tabs" role="tablist" aria-label="登录方式列表">
                        @foreach ($methods as $method)
                            <label for="method-{{ $method['id'] }}" class="method-tab">
                                <span class="method-tab-title">{{ $method['label'] }}</span>
                                <span class="method-tab-summary">{{ $method['summary'] }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="method-panels">
                    @if (in_array('email-code', $methodIds, true))
                    <section class="method-panel method-panel-email-code" aria-label="邮箱验证码登录">
                        <div class="panel-header">
                            <div>
                                <p class="panel-eyebrow">邮箱验证码</p>
                                <h2 class="panel-title">通过邮箱收取验证码登录</h2>
                                <p class="panel-desc">适合首次登录、忘记密码，或在不方便输入密码的设备上使用。</p>
                            </div>
                            <div class="panel-note">无需记住密码</div>
                        </div>

                        <div class="method-flow">
                            <form action="{{ route('auth.code.send') }}" method="POST" class="form-block" novalidate>
                                @csrf
                                <input type="hidden" name="login_method" value="email-code">
                                <input type="hidden" name="channel" value="email">

                                <div class="field">
                                    <label for="send-target">邮箱地址</label>
                                    <input
                                        id="send-target"
                                        type="email"
                                        name="target"
                                        value="{{ $emailCodeTarget }}"
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

                            <div class="flow-divider" aria-hidden="true"><span>然后输入验证码</span></div>

                            <form action="{{ route('auth.code.verify') }}" method="POST" class="form-block" novalidate>
                                @csrf
                                <input type="hidden" name="login_method" value="email-code">
                                <input type="hidden" name="channel" value="email">
                                <input type="hidden" name="target" value="{{ $emailCodeTarget }}">

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
                        </div>
                    </section>
                    @endif

                    @if (in_array('email-password', $methodIds, true))
                    <section class="method-panel method-panel-email-password" aria-label="邮箱密码登录">
                        <div class="panel-header panel-header-single">
                            <div>
                                <p class="panel-eyebrow">邮箱密码</p>
                                <h2 class="panel-title">通过邮箱和密码直接登录</h2>
                                <p class="panel-desc">适合已有账号、常用设备和需要快速进入后台的成员。</p>
                            </div>
                            <div class="panel-note">常用设备更高效</div>
                        </div>

                        <form action="{{ route('auth.password.login') }}" method="POST" class="form-block" novalidate>
                            @csrf
                            <input type="hidden" name="login_method" value="email-password">

                            <div class="field">
                                <label for="password-email">邮箱地址</label>
                                <input
                                    id="password-email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    class="input-field"
                                    placeholder="name@example.com"
                                    autocomplete="username"
                                >
                            </div>

                            <div class="field">
                                <label for="password-value">密码</label>
                                <input
                                    id="password-value"
                                    type="password"
                                    name="password"
                                    class="input-field"
                                    placeholder="请输入密码"
                                    autocomplete="current-password"
                                >
                            </div>

                            <div class="method-tip-list">
                                <div class="method-tip-item">支持管理员账号与已设置密码的成员账号直接登录</div>
                                <div class="method-tip-item">登录成功后会继续沿用当前会话跳转逻辑</div>
                            </div>

                            <button type="submit" class="btn-verify btn-password">
                                使用邮箱密码登录
                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </form>
                    </section>
                    @endif

                    @if (in_array('wechat', $methodIds, true))
                    <section class="method-panel method-panel-wechat" aria-label="微信扫码登录">
                        <div class="panel-header panel-header-single">
                            <div>
                                <p class="panel-eyebrow">微信扫码</p>
                                <h2 class="panel-title">使用微信扫码确认登录</h2>
                                <p class="panel-desc">支持真实微信开放平台扫码登录；未配置时自动回退到内置演示二维码流程。</p>
                            </div>
                            <div class="panel-note panel-note-wechat">{{ $providerMeta['wechat']['mode'] === 'oauth' ? '官方 OAuth' : '演示模式' }}</div>
                        </div>

                        <div class="qr-panel">
                            <div class="qr-visual qr-visual-wechat">
                                <svg aria-hidden="true" class="social-svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.582.582 0 0 1-.023-.156.49.49 0 0 1 .201-.398C23.024 18.48 24 16.82 24 14.98c0-3.21-2.931-5.837-6.656-6.088V8.89c-.135-.01-.269-.03-.406-.03zm-2.53 3.274c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.97-.982zm4.844 0c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982z"/>
                                </svg>
                                <span>微信安全扫码登录</span>
                            </div>

                            <p class="text-sm text-emerald-100/90">{{ $providerMeta['wechat']['helper_text'] }}</p>

                            @if ($providerEnabled['wechat'] && $providerMeta['wechat']['available'])
                                <a href="{{ route('auth.social.redirect', 'wechat') }}" class="btn-social btn-social-wechat">{{ $providerMeta['wechat']['action_label'] }}</a>
                            @else
                                <button type="button" class="btn-social btn-social-disabled" disabled>{{ $providerEnabled['wechat'] ? $providerMeta['wechat']['action_label'] : '微信扫码暂未开启' }}</button>
                            @endif
                        </div>
                    </section>
                    @endif

                    @if (in_array('qq', $methodIds, true))
                    <section class="method-panel method-panel-qq" aria-label="QQ扫码登录">
                        <div class="panel-header panel-header-single">
                            <div>
                                <p class="panel-eyebrow">QQ 扫码</p>
                                <h2 class="panel-title">使用 QQ 扫码确认登录</h2>
                                <p class="panel-desc">支持真实 QQ 互联扫码登录；未配置时自动回退到内置演示二维码流程。</p>
                            </div>
                            <div class="panel-note panel-note-qq">{{ $providerMeta['qq']['mode'] === 'oauth' ? '官方 OAuth' : '演示模式' }}</div>
                        </div>

                        <div class="qr-panel">
                            <div class="qr-visual qr-visual-qq">
                                <svg aria-hidden="true" class="social-svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.003 2c-2.265 0-6.29 1.364-6.29 7.325v1.195S3.55 14.96 3.55 17.474c0 .665.17 1.025.281 1.025.114 0 .902-.484 1.748-2.072 0 0-.18 2.197 1.904 3.967 0 0-1.77.495-1.77 1.182 0 .686 4.078.43 6.29.43 2.213 0 6.29.256 6.29-.43 0-.687-1.77-1.182-1.77-1.182 2.085-1.77 1.905-3.967 1.905-3.967.846 1.588 1.634 2.072 1.748 2.072.11 0 .281-.36.281-1.025 0-2.514-2.164-6.954-2.164-6.954V9.325C18.29 3.364 14.268 2 12.003 2z"/>
                                </svg>
                                <span>QQ 客户端扫码登录</span>
                            </div>

                            <p class="text-sm text-sky-100/90">{{ $providerMeta['qq']['helper_text'] }}</p>

                            @if ($providerEnabled['qq'] && $providerMeta['qq']['available'])
                                <a href="{{ route('auth.social.redirect', 'qq') }}" class="btn-social btn-social-qq">{{ $providerMeta['qq']['action_label'] }}</a>
                            @else
                                <button type="button" class="btn-social btn-social-disabled" disabled>{{ $providerEnabled['qq'] ? $providerMeta['qq']['action_label'] : 'QQ 扫码暂未开启' }}</button>
                            @endif
                        </div>
                    </section>
                    @endif
                </div>
                @endif
            </div>

            <p class="card-footer">登录即表示同意我们的服务条款与隐私政策</p>
        </div>
    </div>
@endsection

@push('styles')
<style>
*, *::before, *::after { box-sizing: border-box; }

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

.orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    will-change: transform;
}
.orb-1 {
    width: 650px;
    height: 650px;
    top: -20%;
    left: -12%;
    background: radial-gradient(circle, rgba(99,102,241,.22) 0%, transparent 65%);
    filter: blur(65px);
    animation: orbDrift1 14s ease-in-out infinite alternate;
}
.orb-2 {
    width: 480px;
    height: 480px;
    bottom: -12%;
    right: -8%;
    background: radial-gradient(circle, rgba(52,211,153,.18) 0%, transparent 65%);
    filter: blur(65px);
    animation: orbDrift2 18s ease-in-out infinite alternate;
}
@keyframes orbDrift1 {
    from { transform: translate(0, 0) scale(1); }
    to { transform: translate(45px, 55px) scale(1.07); }
}
@keyframes orbDrift2 {
    from { transform: translate(0, 0) scale(1); }
    to { transform: translate(-55px, -40px) scale(1.09); }
}

.dev-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
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

.login-card {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 820px;
    background: rgba(255, 255, 255, .98);
    border-radius: 28px;
    padding: 2.5rem;
    box-shadow:
        0 0 0 1px rgba(255,255,255,.05),
        0 32px 72px -12px rgba(0,0,0,.6),
        inset 0 0 0 1px rgba(0,0,0,.035);
    animation: cardIn .65s cubic-bezier(.16,1,.3,1) both;
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(28px) scale(.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.card-header {
    text-align: center;
    margin-bottom: 1.5rem;
}
.brand-mark {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    text-decoration: none;
    margin-bottom: .875rem;
    transition: opacity .15s ease;
}
.brand-mark:hover { opacity: .7; }
.brand-mark > span:first-child {
    font-size: 1.5rem;
    line-height: 1;
}
.brand-text {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.0625rem;
    color: #111827;
    letter-spacing: -.03em;
}
.auth-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 0 .95rem;
    padding: .38rem .8rem;
    border-radius: 999px;
    border: 1px solid rgba(99,102,241,.18);
    background: rgba(99,102,241,.08);
    color: #4f46e5;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .04em;
}
.page-title {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 1.875rem;
    color: #0f172a;
    letter-spacing: -.04em;
    line-height: 1.12;
    margin: 0 0 .5rem;
}
.page-subtitle {
    font-size: .9375rem;
    color: #64748b;
    line-height: 1.55;
    margin: 0;
}

.section-kicker {
    margin: 1rem 0 .85rem;
    color: #64748b;
    font-size: .74rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
}

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
.msg-error svg,
.msg-ok svg {
    width: 1.125rem;
    height: 1.125rem;
    flex-shrink: 0;
    margin-top: .1rem;
}
.msg-error svg { color: #dc2626; }
.msg-ok svg { color: #16a34a; }
.msg-title { font-weight: 600; margin-bottom: .25rem; color: #7f1d1d; }
.msg-list { list-style: none; padding: 0; margin: 0; }
.msg-list li + li { margin-top: .2rem; }

.method-switcher {
    position: relative;
}
.method-toggle {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.method-tabs {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
}
.method-tab {
    display: flex;
    flex-direction: column;
    gap: .32rem;
    min-height: 84px;
    padding: 1rem;
    border-radius: 18px;
    border: 1px solid #dbe3f0;
    background: #f8fafc;
    cursor: pointer;
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.method-tab:hover {
    transform: translateY(-1px);
    border-color: #c7d2fe;
    background: #ffffff;
    box-shadow: 0 10px 20px rgba(15,23,42,.06);
}
.method-tab-title {
    font-size: .95rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
}
.method-tab-summary {
    font-size: .76rem;
    color: #64748b;
    line-height: 1.5;
}

#method-email-code:checked ~ .method-tabs label[for="method-email-code"],
#method-email-password:checked ~ .method-tabs label[for="method-email-password"],
#method-wechat:checked ~ .method-tabs label[for="method-wechat"],
#method-qq:checked ~ .method-tabs label[for="method-qq"] {
    background: linear-gradient(180deg, #eef2ff 0%, #ffffff 100%);
    border-color: #818cf8;
    box-shadow: 0 12px 24px rgba(99,102,241,.12);
}
#method-email-code:checked ~ .method-tabs label[for="method-email-code"] .method-tab-title,
#method-email-password:checked ~ .method-tabs label[for="method-email-password"] .method-tab-title,
#method-wechat:checked ~ .method-tabs label[for="method-wechat"] .method-tab-title,
#method-qq:checked ~ .method-tabs label[for="method-qq"] .method-tab-title {
    color: #4338ca;
}

.method-panels {
    margin-top: 1.1rem;
}
.method-panel {
    display: none;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding: 1.5rem;
}
#method-email-code:checked ~ .method-panels .method-panel-email-code,
#method-email-password:checked ~ .method-panels .method-panel-email-password,
#method-wechat:checked ~ .method-panels .method-panel-wechat,
#method-qq:checked ~ .method-panels .method-panel-qq {
    display: block;
}

.panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.panel-header-single {
    margin-bottom: 1.5rem;
}
.panel-eyebrow {
    margin: 0 0 .35rem;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #6366f1;
}
.panel-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}
.panel-desc {
    margin: .45rem 0 0;
    font-size: .875rem;
    color: #64748b;
    line-height: 1.65;
    max-width: 48rem;
}
.panel-note {
    flex-shrink: 0;
    padding: .48rem .8rem;
    border-radius: 999px;
    background: #eef2ff;
    color: #4338ca;
    font-size: .78rem;
    font-weight: 700;
}
.panel-note-wechat {
    background: #f0fdf4;
    color: #15803d;
}
.panel-note-qq {
    background: #eff6ff;
    color: #1d4ed8;
}

.method-flow {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    gap: 1rem;
    align-items: stretch;
}
.flow-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 52px;
    color: #94a3b8;
    font-size: .76rem;
    font-weight: 700;
    letter-spacing: .04em;
}
.flow-divider span {
    writing-mode: vertical-rl;
    text-orientation: mixed;
}

.form-block {
    display: flex;
    flex-direction: column;
    gap: .95rem;
    padding: 1.125rem;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    background: rgba(255,255,255,.88);
}
.field {
    display: flex;
    flex-direction: column;
    gap: .35rem;
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
}

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
.input-otp {
    text-align: center !important;
    font-size: 1.25rem !important;
    letter-spacing: .38em !important;
    padding-left: .5rem !important;
    padding-right: .5rem !important;
    font-family: 'Courier New', monospace !important;
    font-weight: 700 !important;
    color: #0f172a !important;
}

.btn-send,
.btn-verify,
.btn-social {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    width: 100%;
    padding: .875rem 1.25rem;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-size: .875rem;
    font-weight: 700;
    letter-spacing: .01em;
    transition: background .15s ease, transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.btn-send {
    background: #111827;
    color: #f9fafb;
}
.btn-send:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(0,0,0,.22);
}
.btn-verify {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(99,102,241,.38);
}
.btn-verify:hover {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(99,102,241,.48);
}
.btn-password {
    margin-top: .2rem;
}
.btn-send:active,
.btn-verify:active,
.btn-social:active {
    transform: translateY(0);
    box-shadow: none;
}
.btn-send svg,
.btn-verify svg {
    width: 1rem;
    height: 1rem;
}

.method-tip-list {
    display: grid;
    gap: .55rem;
}
.method-tip-item {
    position: relative;
    padding-left: 1rem;
    font-size: .8rem;
    color: #475569;
    line-height: 1.55;
}
.method-tip-item::before {
    content: '';
    position: absolute;
    top: .48rem;
    left: 0;
    width: .42rem;
    height: .42rem;
    border-radius: 999px;
    background: #818cf8;
}

.qr-panel {
    display: grid;
    gap: 1rem;
}
.qr-visual {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .75rem;
    min-height: 140px;
    border-radius: 18px;
    border: 1px dashed #cbd5e1;
    font-size: 1rem;
    font-weight: 700;
}
.qr-visual-wechat {
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
    color: #15803d;
}
.qr-visual-qq {
    background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
    color: #1d4ed8;
}
.social-svg {
    width: 1.6rem;
    height: 1.6rem;
    flex-shrink: 0;
}
.btn-social-wechat {
    background: #16a34a;
    color: #ffffff;
    box-shadow: 0 10px 24px rgba(22,163,74,.22);
}
.btn-social-wechat:hover {
    background: #15803d;
    transform: translateY(-1px);
}
.btn-social-qq {
    background: #2563eb;
    color: #ffffff;
    box-shadow: 0 10px 24px rgba(37,99,235,.22);
}
.btn-social-qq:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}
.btn-social-disabled {
    background: #e2e8f0;
    color: #64748b;
    cursor: not-allowed;
}

.card-footer {
    text-align: center;
    font-size: .72rem;
    color: #94a3b8;
    margin-top: 1.5rem;
    line-height: 1.6;
    padding-top: 1.25rem;
    border-top: 1px solid #f1f5f9;
}

@media (max-width: 860px) {
    .login-card {
        max-width: 640px;
        padding: 2rem;
    }
    .method-tabs {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .method-flow {
        grid-template-columns: 1fr;
    }
    .flow-divider {
        min-height: 36px;
    }
    .flow-divider span {
        writing-mode: initial;
    }
}

@media (max-width: 560px) {
    .auth-wrap {
        padding: 1.25rem .85rem;
    }
    .login-card {
        padding: 1.5rem;
        border-radius: 22px;
    }
    .page-title {
        font-size: 1.625rem;
    }
    .method-tabs {
        grid-template-columns: 1fr;
    }
    .panel-header {
        flex-direction: column;
    }
    .panel-note {
        align-self: flex-start;
    }
    .orb-1 {
        width: 320px;
        height: 320px;
    }
    .orb-2 {
        width: 240px;
        height: 240px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .orb,
    .login-card {
        animation: none !important;
    }
    .method-tab,
    .btn-send,
    .btn-verify,
    .btn-social {
        transition: none !important;
    }
}
</style>
@endpush
