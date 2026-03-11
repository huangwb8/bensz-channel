@extends('layouts.app')

@section('content')
    @php
        $isCreatingUser = old('creating_user') === '1';
        $emailCodeEnabled = (bool) ($createUserOptions['email_code_enabled'] ?? false);
        $emailPasswordEnabled = (bool) ($createUserOptions['email_password_enabled'] ?? false);
        $passwordRequired = ! $emailCodeEnabled;
    @endphp

    <div data-admin-users-page>
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">用户管理</h2>
                <p class="mt-1 text-sm text-gray-500">查看社区成员、调整角色，并快速维护关键信息。</p>
            </div>
            <div class="icon-action-group">
                <x-icon-button :href="route('admin.articles.index')" icon="document" label="文章管理" title="文章管理" />
                <x-icon-button :href="route('admin.channels.index')" icon="folder" label="频道管理" title="频道管理" />
            </div>
        </div>

        <section class="user-ops-dashboard mt-4 rounded-2xl p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="user-ops-dashboard-kicker text-xs font-semibold uppercase tracking-[0.22em]">Dashboard</p>
                    <h3 class="mt-1 text-base font-semibold">用户运营仪表盘</h3>
                </div>
                <div class="user-ops-dashboard-badge rounded-full px-2.5 py-0.5 text-xs">
                    最近 7 天数据
                </div>
            </div>

            <div class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                @foreach($dashboard['cards'] as $card)
                    <div class="user-ops-dashboard-card rounded-xl p-3 backdrop-blur-sm">
                        <p class="user-ops-dashboard-muted text-xs">{{ $card['label'] }}</p>
                        <p class="user-ops-dashboard-value mt-1 text-2xl font-semibold">{{ $card['value'] }}</p>
                        <p class="user-ops-dashboard-soft mt-1 text-xs">{{ $card['helper'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 grid gap-3 xl:grid-cols-[minmax(0,1fr)_280px]">
                <div class="user-ops-dashboard-panel rounded-xl p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <h4 class="user-ops-dashboard-title text-sm font-semibold">最近 7 天登录 / 活跃分布</h4>
                        <div class="user-ops-dashboard-muted flex flex-wrap items-center gap-2.5 text-xs">
                            <span class="inline-flex items-center gap-1.5"><span class="dashboard-legend dashboard-legend-login"></span>登录</span>
                            <span class="inline-flex items-center gap-1.5"><span class="dashboard-legend dashboard-legend-comments"></span>评论</span>
                            <span class="inline-flex items-center gap-1.5"><span class="dashboard-legend dashboard-legend-articles"></span>发文</span>
                        </div>
                    </div>

                    <div class="dashboard-chart" role="img" aria-label="最近 7 天登录、评论与发文情况图表">
                        @foreach($dashboard['series'] as $point)
                            <div class="dashboard-chart-column">
                                <div class="dashboard-chart-bars">
                                    @foreach([
                                        'login' => ['label' => '登录', 'class' => 'dashboard-bar-login'],
                                        'comments' => ['label' => '评论', 'class' => 'dashboard-bar-comments'],
                                        'articles' => ['label' => '发文', 'class' => 'dashboard-bar-articles'],
                                    ] as $key => $meta)
                                        @php
                                            $value = (int) $point[$key];
                                            $height = $value > 0
                                                ? max(12, (int) round(($value / $dashboard['chart_max']) * 100))
                                                : 0;
                                        @endphp
                                        <div class="dashboard-chart-bar-slot">
                                            <div
                                                class="dashboard-chart-bar {{ $meta['class'] }}"
                                                style="height: {{ $height }}%;"
                                                title="{{ $point['full_label'] }} {{ $meta['label'] }}：{{ $value }}"
                                                data-value="{{ $value }}"
                                            ></div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="dashboard-chart-label mt-2 text-center text-xs">{{ $point['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="user-ops-dashboard-panel rounded-xl p-3">
                    <h4 class="user-ops-dashboard-title text-sm font-semibold mb-2">用户统计</h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2">
                            <span class="user-ops-dashboard-muted text-xs">总用户数</span>
                            <span class="user-ops-dashboard-value text-lg font-semibold">{{ $stats['total'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2">
                            <span class="user-ops-dashboard-muted text-xs">管理员</span>
                            <span class="user-ops-dashboard-value text-lg font-semibold">{{ $stats['admins'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2">
                            <span class="user-ops-dashboard-muted text-xs">成员</span>
                            <span class="user-ops-dashboard-value text-lg font-semibold">{{ $stats['members'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2">
                            <span class="user-ops-dashboard-muted text-xs">7 日活跃</span>
                            <span class="user-ops-dashboard-value text-lg font-semibold">{{ $stats['recent'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <form action="{{ route('admin.users.store') }}" method="POST" class="mt-6 rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="creating_user" value="1">

            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">手动添加用户</div>
                    <h3 class="mt-3 text-lg font-semibold text-gray-900">新增用户</h3>
                    <p class="mt-1 text-sm text-gray-500">管理员可直接创建账号、设置角色，并补齐邮箱、手机号与头像资料。</p>
                </div>
                <div class="rounded-xl border border-dashed border-gray-200 bg-white px-4 py-3 text-xs text-gray-500">
                    <p>登录建议：必须填写邮箱。</p>
                    @if($passwordRequired)
                        <p class="mt-1 text-amber-600">当前站点未启用邮箱验证码，新用户必须设置初始密码。</p>
                    @elseif($emailPasswordEnabled)
                        <p class="mt-1">可选设置初始密码，便于用户首次通过邮箱密码登录。</p>
                    @else
                        <p class="mt-1">新用户可通过邮箱验证码完成首次登录。</p>
                    @endif
                </div>
            </div>

            @if($isCreatingUser && $errors->any())
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    新增用户失败，请检查资料后重试。
                </div>
            @endif

            <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.1fr)_180px_minmax(0,1.15fr)_180px]">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">昵称 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input-field h-11" placeholder="例如：社区运营小助手" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">角色 <span class="text-red-500">*</span></label>
                    <select name="role" class="input-field h-11" required>
                        <option value="{{ \App\Models\User::ROLE_MEMBER }}" @selected(old('role', \App\Models\User::ROLE_MEMBER) === \App\Models\User::ROLE_MEMBER)>成员</option>
                        <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(old('role') === \App\Models\User::ROLE_ADMIN)>管理员</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">邮箱 <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="input-field h-11" placeholder="name@example.com" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">手机号</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="input-field h-11" placeholder="选填，支持自动去除空格与符号">
                </div>
            </div>

            <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">头像链接</label>
                    <input type="url" name="avatar_url" value="{{ old('avatar_url') }}" class="input-field h-11" placeholder="https://cdn.example.com/avatar.png">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">简介 / 职责</label>
                    <input type="text" name="bio" value="{{ old('bio') }}" class="input-field h-11" placeholder="例如：负责频道运营与用户支持">
                </div>
            </div>

            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                        初始密码
                        @if($passwordRequired)
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <input type="password" name="password" class="input-field h-11" placeholder="至少 8 位" autocomplete="new-password" @if($passwordRequired) required @endif>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">确认初始密码</label>
                    <input type="password" name="password_confirmation" class="input-field h-11" placeholder="再次输入初始密码" autocomplete="new-password" @if($passwordRequired) required @endif>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-gray-500">创建后会自动分配稳定用户 ID，并初始化默认通知偏好。</p>
                <button type="submit" class="btn-primary">创建用户</button>
            </div>
        </form>

        <form action="{{ route('admin.users.index') }}" method="GET" class="mt-6 grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-5 lg:grid-cols-[minmax(0,2fr)_180px_auto]">
            <input
                type="text"
                name="q"
                value="{{ $filters['q'] }}"
                class="input-field h-11"
                placeholder="搜索用户ID、昵称、邮箱或手机号"
            >
            <select name="role_filter" class="input-field h-11">
                <option value="">全部角色</option>
                <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected($filters['role'] === \App\Models\User::ROLE_ADMIN)>管理员</option>
                <option value="{{ \App\Models\User::ROLE_MEMBER }}" @selected($filters['role'] === \App\Models\User::ROLE_MEMBER)>成员</option>
            </select>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">筛选</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">重置</a>
            </div>
        </form>

        <form
            id="bulk-delete-form"
            action="{{ route('admin.users.bulk-destroy') }}"
            method="POST"
            class="mt-4 flex flex-col gap-3 rounded-2xl border border-red-100 bg-red-50/80 p-4 lg:flex-row lg:items-center lg:justify-between"
            onsubmit="return confirm('确认批量删除当前选中的普通用户吗？这些用户的文章、评论、会话和密码重置记录都会一并清理。');"
        >
            @csrf
            @method('DELETE')
            <input type="hidden" name="q" value="{{ $filters['q'] }}">
            <input type="hidden" name="role_filter" value="{{ $filters['role'] }}">

            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-700">
                <label class="inline-flex items-center gap-3 font-medium text-gray-800">
                    <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900" data-bulk-select-all>
                    <span>全选当前页可删用户</span>
                </label>
                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 shadow-sm">
                    已选 <span class="mx-1 font-semibold text-gray-900" data-bulk-selected-count>0</span> 人
                </span>
                <button type="button" class="text-sm font-medium text-gray-500 transition hover:text-gray-800" data-bulk-clear-selection>
                    清空选择
                </button>
            </div>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-200 disabled:text-red-50"
                data-bulk-delete-submit
            >
                批量删除
            </button>
        </form>
    </section>

    <section class="mt-6 space-y-3">
        @forelse($users as $managedUser)
            @php
                $isEditingRow = in_array($managedUser->id, [
                    (int) old('editing_user_id', 0),
                    (int) old('ban_user_id', 0),
                ], true);
                $isBanned = $managedUser->isBanned();
                $banUntilValue = old('ban_until', optional($managedUser->banned_until)->format('Y-m-d\\TH:i'));
                $banDuration = old('ban_duration');

                if (! is_string($banDuration) || $banDuration === '') {
                    $banDuration = $isBanned
                        ? ($managedUser->banned_until === null ? 'permanent' : 'custom')
                        : '7d';
                }
                $formName = $isEditingRow ? old('name', $managedUser->name) : $managedUser->name;
                $formRole = $isEditingRow ? old('role', $managedUser->role) : $managedUser->role;
                $formEmail = $isEditingRow ? old('email', $managedUser->email) : $managedUser->email;
                $formPhone = $isEditingRow ? old('phone', $managedUser->phone) : $managedUser->phone;
                $formAvatarUrl = $isEditingRow ? old('avatar_url', $managedUser->avatar_url) : $managedUser->avatar_url;
                $formBio = $isEditingRow ? old('bio', $managedUser->bio) : $managedUser->bio;
                $updateFormId = 'user-update-'.$managedUser->id;
                $deleteFormId = 'user-delete-'.$managedUser->id;
                $panelId = 'user-panel-'.$managedUser->id;
                $canDelete = ! $managedUser->isAdmin();
            @endphp

            <section class="article-card user-management-card" data-user-card data-user-id="{{ $managedUser->id }}" data-initial-expanded="{{ $isEditingRow ? 'true' : 'false' }}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white shadow-sm">
                            @if($canDelete)
                                <input
                                    type="checkbox"
                                    name="selected_user_ids[]"
                                    value="{{ $managedUser->id }}"
                                    form="bulk-delete-form"
                                    class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                    data-bulk-select-item
                                    aria-label="选择用户：{{ $managedUser->name }}"
                                >
                            @else
                                <span class="text-xs font-semibold text-gray-400">Admin</span>
                            @endif
                        </div>

                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                @if($managedUser->avatar_url)
                                    <img src="{{ $managedUser->avatar_url }}" alt="{{ $managedUser->name }} 的头像" class="h-full w-full object-cover">
                                @else
                                    {{ mb_substr($managedUser->name, 0, 1) }}
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-base font-semibold text-gray-900">{{ $managedUser->name }}</h3>
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                        ID {{ $managedUser->user_id }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $managedUser->isAdmin() ? 'bg-violet-100 text-violet-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $managedUser->isAdmin() ? '管理员' : '成员' }}
                                    </span>
                                    @if($isBanned)
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">
                                            已封禁{{ $managedUser->banned_until ? '至 '.$managedUser->banned_until->format('Y-m-d H:i') : '（永久）' }}
                                        </span>
                                    @endif
                                    @if(auth()->id() === $managedUser->id)
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">当前登录</span>
                                    @endif
                                </div>

                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                    <span>{{ $managedUser->email ?: '未填写邮箱' }}</span>
                                    <span>{{ $managedUser->phone ?: '未填写手机号' }}</span>
                                    <span>发文 {{ $managedUser->articles_count }}</span>
                                    <span>评论 {{ $managedUser->comments_count }}</span>
                                    <span>最近活跃 {{ optional($managedUser->last_seen_at)->format('Y-m-d H:i') ?? '暂无记录' }}</span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-400">
                                    <span>邮箱{{ $managedUser->email_verified_at ? '已验证' : '未验证' }}</span>
                                    <span>手机{{ $managedUser->phone_verified_at ? '已验证' : '未验证' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="icon-action-group shrink-0">
                        <x-icon-button
                            :form="$updateFormId"
                            icon="save"
                            label="保存用户"
                            title="保存用户"
                            :aria-label="'保存用户：'.$managedUser->name"
                            variant="primary"
                            type="submit"
                        />

                        <button
                            type="submit"
                            class="icon-action icon-action-danger"
                            title="{{ $canDelete ? '删除用户' : '管理员不可删除' }}"
                            aria-label="{{ $canDelete ? '删除用户：'.$managedUser->name : '管理员不可删除：'.$managedUser->name }}"
                            @if($canDelete)
                                form="{{ $deleteFormId }}"
                            @endif
                            @disabled(! $canDelete)
                        >
                            <x-icon name="trash" class="h-5 w-5" />
                            <span class="sr-only">删除用户</span>
                        </button>

                        <button
                            type="button"
                            class="icon-action"
                            title="{{ $isEditingRow ? '收起用户' : '展开用户' }}"
                            aria-label="{{ $isEditingRow ? '收起用户：'.$managedUser->name : '展开用户：'.$managedUser->name }}"
                            aria-controls="{{ $panelId }}"
                            aria-expanded="{{ $isEditingRow ? 'true' : 'false' }}"
                            data-user-card-toggle
                            data-label-expand="展开用户：{{ $managedUser->name }}"
                            data-label-collapse="收起用户：{{ $managedUser->name }}"
                        >
                            <span @class(['hidden' => $isEditingRow]) data-toggle-icon-expand>
                                <x-icon name="chevron-down" class="h-5 w-5" />
                            </span>
                            <span @class(['hidden' => ! $isEditingRow]) data-toggle-icon-collapse>
                                <x-icon name="chevron-up" class="h-5 w-5" />
                            </span>
                            <span class="sr-only" data-toggle-text>{{ $isEditingRow ? '收起用户' : '展开用户' }}</span>
                        </button>
                    </div>
                </div>

                <div id="{{ $panelId }}" class="user-management-card-panel mt-4" data-user-card-panel>
                    <form id="{{ $updateFormId }}" action="{{ route('admin.users.update', $managedUser) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="editing_user_id" value="{{ $managedUser->id }}">
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                        <input type="hidden" name="role_filter" value="{{ $filters['role'] }}">

                        @if($isEditingRow && $errors->any())
                            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                当前用户保存失败，请检查输入后重试。
                            </div>
                        @endif

                        <div class="grid gap-3 xl:grid-cols-[minmax(0,1.1fr)_160px_minmax(0,1.25fr)_180px]">
                            <input
                                type="text"
                                name="name"
                                value="{{ $formName }}"
                                class="input-field h-10"
                                placeholder="昵称"
                                aria-label="昵称"
                                required
                            >
                            <select name="role" class="input-field h-10" aria-label="角色">
                                <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected($formRole === \App\Models\User::ROLE_ADMIN)>管理员</option>
                                <option value="{{ \App\Models\User::ROLE_MEMBER }}" @selected($formRole === \App\Models\User::ROLE_MEMBER)>成员</option>
                            </select>
                            <input
                                type="email"
                                name="email"
                                value="{{ $formEmail }}"
                                class="input-field h-10"
                                placeholder="邮箱"
                                aria-label="邮箱"
                            >
                            <input
                                type="text"
                                name="phone"
                                value="{{ $formPhone }}"
                                class="input-field h-10"
                                placeholder="手机号"
                                aria-label="手机号"
                            >
                        </div>

                        <div class="mt-3 grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1.4fr)]">
                            <input
                                type="url"
                                name="avatar_url"
                                value="{{ $formAvatarUrl }}"
                                class="input-field h-10"
                                placeholder="头像链接"
                                aria-label="头像链接"
                            >
                            <input
                                type="text"
                                name="bio"
                                value="{{ $formBio }}"
                                class="input-field h-10"
                                placeholder="简介 / 职责"
                                aria-label="简介"
                            >
                        </div>
                    </form>

                    <form action="{{ route('admin.users.ban', $managedUser) }}" method="POST" class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        @csrf
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                        <input type="hidden" name="role_filter" value="{{ $filters['role'] }}">
                        <input type="hidden" name="ban_user_id" value="{{ $managedUser->id }}">

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">封禁设置</p>
                                <p class="mt-1 text-xs text-gray-500">选择封禁时长或自定义截止时间，永久封禁需显式选择。</p>
                            </div>
                            @if($isBanned)
                                <button type="submit" class="btn-secondary" form="unban-form-{{ $managedUser->id }}">解除封禁</button>
                            @endif
                        </div>

                        @if(old('ban_user_id') == $managedUser->id && $errors->any())
                            <div class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                                封禁设置未生效，请检查输入。
                            </div>
                        @endif

                        <div class="mt-3 grid gap-3 lg:grid-cols-[200px_minmax(0,1fr)_200px]">
                            <select name="ban_duration" class="input-field h-10" aria-label="封禁时长" @disabled($managedUser->isAdmin())>
                                <option value="1d" @selected($banDuration === '1d')>封禁 1 天</option>
                                <option value="3d" @selected($banDuration === '3d')>封禁 3 天</option>
                                <option value="7d" @selected($banDuration === '7d')>封禁 7 天</option>
                                <option value="30d" @selected($banDuration === '30d')>封禁 30 天</option>
                                <option value="custom" @selected($banDuration === 'custom')>自定义截止时间</option>
                                <option value="permanent" @selected($banDuration === 'permanent')>永久封禁</option>
                            </select>
                            <input
                                type="datetime-local"
                                name="ban_until"
                                value="{{ $banUntilValue }}"
                                class="input-field h-10"
                                placeholder="选择封禁截止时间"
                                @disabled($managedUser->isAdmin())
                            >
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-200 disabled:text-red-50"
                                @disabled($managedUser->isAdmin())
                            >
                                执行封禁
                            </button>
                        </div>

                        @if($managedUser->isAdmin())
                            <p class="mt-2 text-xs text-gray-400">管理员账号不可封禁。</p>
                        @endif
                    </form>

                    <form id="unban-form-{{ $managedUser->id }}" action="{{ route('admin.users.unban', $managedUser) }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                        <input type="hidden" name="role_filter" value="{{ $filters['role'] }}">
                    </form>
                </div>

                @if($canDelete)
                    <form
                        id="{{ $deleteFormId }}"
                        action="{{ route('admin.users.destroy', $managedUser) }}"
                        method="POST"
                        class="hidden"
                        onsubmit="return confirm('确认删除普通用户“{{ $managedUser->name }}”吗？该用户的文章、评论和登录状态都会一并清理。');"
                    >
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                        <input type="hidden" name="role_filter" value="{{ $filters['role'] }}">
                    </form>
                @endif
            </section>
        @empty
            <section class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                当前筛选条件下没有用户。
            </section>
        @endforelse

        @if($users->hasPages())
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </section>
    </div>
@endsection
