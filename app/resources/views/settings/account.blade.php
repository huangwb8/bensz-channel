@extends('layouts.app')

@section('content')
    @php
        $currentUser = auth()->user();
    @endphp

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
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                        用户ID {{ $currentUser?->user_id }}
                    </span>
                </div>
            </div>

            @php
                $avatarType = old('avatar_type', $currentUser?->avatar_type ?? 'generated');
                $avatarStyle = old('avatar_style', $currentUser?->avatar_style ?? 'classic_letter');
            @endphp

            <form action="{{ route('settings.account.profile.update') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6">
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
                            <span class="text-sm font-medium text-gray-700">邮箱</span>
                            <input type="email" name="email" value="{{ old('email', $currentUser?->email) }}" class="input-field" maxlength="120" placeholder="name@example.com">
                        </label>
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-gray-700">手机号</span>
                            <input type="text" name="phone" value="{{ old('phone', $currentUser?->phone) }}" class="input-field" maxlength="32" placeholder="13800000000">
                        </label>
                    </div>

                    <section class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <div class="flex flex-wrap items-start gap-4">
                            <div class="space-y-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">当前头像</h3>
                                    <p class="mt-1 text-sm text-gray-500">支持默认头像风格、外链头像和本地上传头像三种来源。</p>
                                </div>
                                <x-user-avatar :user="$currentUser" class="h-20 w-20 rounded-[24px] ring-1 ring-gray-200" />
                            </div>

                            <div class="min-w-0 flex-1 space-y-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">头像来源</h3>
                                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                                        <label class="rounded-xl border border-gray-200 bg-white p-3">
                                            <div class="flex items-start gap-3">
                                                <input type="radio" name="avatar_type" value="generated" class="mt-1 h-4 w-4" @checked($avatarType === 'generated')>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">默认风格</div>
                                                    <div class="mt-1 text-xs text-gray-500">完全站内生成，适合默认头像。</div>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="rounded-xl border border-gray-200 bg-white p-3">
                                            <div class="flex items-start gap-3">
                                                <input type="radio" name="avatar_type" value="uploaded" class="mt-1 h-4 w-4" @checked($avatarType === 'uploaded')>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">上传 JPG 或 PNG 头像</div>
                                                    <div class="mt-1 text-xs text-gray-500">文件大小不超过 1MB。</div>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="rounded-xl border border-gray-200 bg-white p-3">
                                            <div class="flex items-start gap-3">
                                                <input type="radio" name="avatar_type" value="external" class="mt-1 h-4 w-4" @checked($avatarType === 'external')>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">外链头像</div>
                                                    <div class="mt-1 text-xs text-gray-500">沿用已有 CDN / 图床资源。</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="block space-y-2">
                                        <span class="text-sm font-medium text-gray-700">上传文件</span>
                                        <input type="file" name="avatar_upload" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="input-field h-auto p-3">
                                        <p class="text-xs text-gray-500">只有在上方选择“上传 JPG 或 PNG 头像”时才会生效。</p>
                                    </label>
                                    <label class="block space-y-2">
                                        <span class="text-sm font-medium text-gray-700">外链头像地址</span>
                                        <input type="url" name="avatar_url" value="{{ old('avatar_url', $currentUser?->avatar_type === 'external' ? $currentUser?->avatar_url : '') }}" class="input-field" maxlength="2048" placeholder="https://example.com/avatar.png">
                                        <p class="text-xs text-gray-500">只有在选择“外链头像”时会作为实际头像使用。</p>
                                    </label>
                                </div>

                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">默认头像风格</h3>
                                    <p class="mt-1 text-sm text-gray-500">选择默认头像时，可在这里切换风格；即使你暂时使用上传/外链头像，风格也会先保存下来，方便以后切回。</p>
                                    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                        @foreach($avatarStyles as $style)
                                            <label class="rounded-2xl border border-gray-200 bg-white p-4 transition hover:border-blue-300 hover:shadow-sm">
                                                <div class="flex items-start gap-3">
                                                    <input type="radio" name="avatar_style" value="{{ $style['id'] }}" class="mt-1 h-4 w-4" @checked($avatarStyle === $style['id'])>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center gap-3">
                                                            <span class="inline-flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl ring-1 ring-gray-200">
                                                                {!! app(\App\Support\AvatarPresenter::class)->preview($currentUser, $style['id']) !!}
                                                            </span>
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900">{{ $style['name'] }}</div>
                                                                <p class="mt-1 text-xs text-gray-500">{{ $style['description'] }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        @error('avatar_upload')
                            <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('avatar_url')
                            <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </section>

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

        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">两步验证</h2>
                    <p class="mt-1 text-sm text-gray-500">开启后，任何登录方式在主验证通过后都需要再输入一次动态验证码或恢复码。</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full px-3 py-1 font-semibold {{ $twoFactorEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $twoFactorEnabled ? '已开启两步验证' : '尚未开启两步验证' }}
                    </span>
                </div>
            </div>

            @if ($twoFactorRecoveryCodes !== [])
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                    <p class="font-semibold">请立即保存以下恢复码</p>
                    <p class="mt-1">每个恢复码只能使用一次。建议离线保存，避免手机丢失后无法登录。</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($twoFactorRecoveryCodes as $recoveryCode)
                            <code class="rounded-lg bg-white px-3 py-2 text-center text-sm font-semibold tracking-[0.2em] text-slate-800">{{ $recoveryCode }}</code>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($twoFactorEnabled)
                <div class="mt-6 space-y-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        两步验证当前已启用。若更换设备，可先使用下方表单重新生成恢复码；若确定停用，请输入当前动态验证码或恢复码完成关闭。
                    </div>

                    <form action="{{ route('settings.account.two-factor.recovery-codes.regenerate') }}" method="POST" class="space-y-4 rounded-xl border border-gray-100 bg-gray-50 p-4">
                        @csrf
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">重新生成恢复码</h3>
                            <p class="mt-1 text-sm text-gray-500">旧恢复码会立即失效。请输入当前动态验证码，或提供一个尚未使用的恢复码进行确认。</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block space-y-2">
                                <span class="text-sm font-medium text-gray-700">动态验证码</span>
                                <input type="text" name="code" class="input-field" inputmode="numeric" autocomplete="one-time-code" placeholder="输入 6 位动态码">
                            </label>
                            <label class="block space-y-2">
                                <span class="text-sm font-medium text-gray-700">恢复码</span>
                                <input type="text" name="recovery_code" class="input-field" autocomplete="off" placeholder="例如 ABCD-EFGH">
                            </label>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                                重新生成恢复码
                            </button>
                        </div>
                    </form>

                    <form action="{{ route('settings.account.two-factor.disable') }}" method="POST" class="space-y-4 rounded-xl border border-red-100 bg-red-50 p-4">
                        @csrf
                        @method('DELETE')
                        <div>
                            <h3 class="text-sm font-semibold text-red-900">关闭两步验证</h3>
                            <p class="mt-1 text-sm text-red-700">关闭后，后续登录将不再要求动态验证码。请输入当前动态验证码或恢复码确认。</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block space-y-2">
                                <span class="text-sm font-medium text-red-900">动态验证码</span>
                                <input type="text" name="code" class="input-field" inputmode="numeric" autocomplete="one-time-code" placeholder="输入 6 位动态码">
                            </label>
                            <label class="block space-y-2">
                                <span class="text-sm font-medium text-red-900">恢复码</span>
                                <input type="text" name="recovery_code" class="input-field" autocomplete="off" placeholder="例如 ABCD-EFGH">
                            </label>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                                关闭两步验证
                            </button>
                        </div>
                    </form>
                </div>
            @elseif ($twoFactorSetup)
                <div class="mt-6 grid gap-6 lg:grid-cols-[280px,1fr]">
                    <div class="two-factor-setup-shell flex items-center justify-center rounded-2xl p-4">
                        <div class="two-factor-qr-frame h-[240px] w-[240px] overflow-hidden rounded-2xl p-3" data-qr-value="{{ $twoFactorSetup['provisioningUri'] }}"></div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">第一步：绑定验证器</h3>
                            <p class="mt-1 text-sm text-gray-500">使用 Google Authenticator、Microsoft Authenticator、1Password 等应用扫描二维码；若扫描不便，也可手动输入密钥。</p>
                        </div>

                        <div class="two-factor-secret-card rounded-xl px-4 py-3">
                            <p class="two-factor-secret-label text-xs font-medium uppercase tracking-[0.2em]">手动密钥</p>
                            <code class="two-factor-secret-value mt-2 block break-all text-sm font-semibold tracking-[0.25em]">{{ $twoFactorSetup['secret'] }}</code>
                        </div>

                        <form action="{{ route('settings.account.two-factor.enable') }}" method="POST" class="two-factor-verify-card space-y-4 rounded-xl p-4">
                            @csrf
                            <div>
                                <h3 class="text-sm font-semibold text-blue-900">第二步：输入动态验证码完成开启</h3>
                                <p class="mt-1 text-sm text-blue-700">绑定成功后，输入验证器当前显示的 6 位动态码。保存后系统会为你生成一次性恢复码。</p>
                            </div>
                            <label class="block space-y-2">
                                <span class="text-sm font-medium text-blue-900">动态验证码</span>
                                <input type="text" name="code" class="input-field" inputmode="numeric" autocomplete="one-time-code" placeholder="例如 123456" required>
                            </label>
                            <div class="flex justify-end">
                                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                    开启两步验证
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </section>
    </section>
@endsection
