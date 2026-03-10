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
        <div class="orb orb-3" aria-hidden="true"></div>

        @if (session('otp_preview'))
            <div class="dev-banner" role="note">
                <span>⚡ 开发验证码预览</span>
                <code>{{ session('otp_preview') }}</code>
            </div>
        @endif

        <div class="login-card">
            <header class="card-header">
                <a href="{{ route('home') }}" class="brand-mark" title="返回主页" aria-label="返回主页">
                    <span aria-hidden="true">💬</span>
                    <span class="brand-text">{{ $siteName ?? 'Bensz Channel' }}</span>
                </a>
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
@vite(['resources/css/auth.css'])
@endpush
