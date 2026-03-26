@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">站点设置</h2>
                <p class="mt-1 text-sm text-gray-500">管理员可以在这里维护站点展示文案、上传限制、主题排程与认证入口。CDN 已拆分为独立页面统一管理。</p>
            </div>
            <div class="icon-action-group">
                <x-icon-button :href="route('admin.articles.index')" icon="document" label="文章管理" title="文章管理" />
                <x-icon-button :href="route('admin.comments.index')" icon="chat-bubble-left-right" label="评论管理" title="评论管理" />
                <x-icon-button :href="route('admin.channels.index')" icon="folder" label="频道管理" title="频道管理" />
                <x-icon-button :href="route('admin.users.index')" icon="users" label="用户管理" title="用户管理" />
                <x-icon-button :href="route('admin.tags.index')" icon="tag" label="标签管理" title="标签管理" />
                <x-icon-button :href="route('admin.cdn-settings.index')" icon="eye" label="CDN 设置" title="CDN 设置" />
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">前台展示与认证入口</h3>
                <p class="mt-1 text-sm text-gray-500">保存后会立即覆盖运行时配置，并自动重建游客静态页与登录页展示。</p>
            </div>
            <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $siteSettingsUsingOverrides ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">
                {{ $siteSettingsUsingOverrides ? '当前使用站点后台覆盖配置' : '当前使用 app/config.toml 默认值' }}
            </div>
        </div>

        <form action="{{ route('admin.site-settings.update') }}" method="POST" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                静态资源加速、对象存储凭证与同步策略已迁移到 <a href="{{ route('admin.cdn-settings.index') }}" class="font-semibold underline decoration-blue-300 underline-offset-2 hover:text-blue-900">CDN 设置</a> 独立页面，本页不再保存 CDN 字段。
            </div>

            <div>
                <label for="app_name" class="mb-2 block text-sm font-medium text-gray-700">APP_NAME</label>
                <input id="app_name" type="text" name="app_name" value="{{ old('app_name', $siteSettingsForm['app_name']) }}" class="input-field h-11" placeholder="Bensz Channel">
                <p class="mt-2 text-xs text-gray-500">用于框架级应用名、系统标题与部分通知默认文案。</p>
            </div>

            <div>
                <label for="site_name" class="mb-2 block text-sm font-medium text-gray-700">SITE_NAME</label>
                <input id="site_name" type="text" name="site_name" value="{{ old('site_name', $siteSettingsForm['site_name']) }}" class="input-field h-11" placeholder="Bensz Channel">
                <p class="mt-2 text-xs text-gray-500">用于前台导航、页面标题与登录页品牌名称展示。</p>
            </div>

            <div>
                <label for="site_tagline" class="mb-2 block text-sm font-medium text-gray-700">SITE_TAGLINE</label>
                <textarea id="site_tagline" name="site_tagline" rows="3" class="input-field min-h-[96px] py-3" placeholder="类 QQ 频道的 Web 社区原型，支持静态游客访问与成员互动">{{ old('site_tagline', $siteSettingsForm['site_tagline']) }}</textarea>
                <p class="mt-2 text-xs text-gray-500">用于首页、副标题与页脚说明展示。</p>
            </div>

            <div>
                <label for="timezone" class="mb-2 block text-sm font-medium text-gray-700">项目时区</label>
                <select id="timezone" name="timezone" class="input-field h-11">
                    <optgroup label="常用时区">
                        @foreach($timezoneOptionGroups['preferred'] as $timezoneValue => $timezoneLabel)
                            <option value="{{ $timezoneValue }}" @selected(old('timezone', $siteSettingsForm['timezone']) === $timezoneValue)>{{ $timezoneLabel }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="全部 IANA 时区">
                        @foreach($timezoneOptionGroups['all'] as $timezoneValue => $timezoneLabel)
                            <option value="{{ $timezoneValue }}" @selected(old('timezone', $siteSettingsForm['timezone']) === $timezoneValue)>{{ $timezoneLabel }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <p class="mt-2 text-xs text-gray-500">默认使用北京时间 <code>Asia/Shanghai</code>。保存后会统一影响文章发布时间自动填充、页面时间格式、相对时间、RSS、SEO 与备份时间戳。</p>
            </div>

            <div>
                <label for="article_image_max_mb" class="mb-2 block text-sm font-medium text-gray-700">文章图片上传上限（MB）</label>
                <input
                    id="article_image_max_mb"
                    type="number"
                    name="article_image_max_mb"
                    value="{{ old('article_image_max_mb', $siteSettingsForm['article_image_max_mb']) }}"
                    class="input-field h-11"
                    min="1"
                    max="100"
                    step="1"
                >
                <p class="mt-2 text-xs text-gray-500">仅影响文章编辑器内粘贴/上传图片的大小限制，默认 50MB。</p>
            </div>

            <div>
                <label for="article_video_max_mb" class="mb-2 block text-sm font-medium text-gray-700">视频上传上限（MB）</label>
                <input
                    id="article_video_max_mb"
                    type="number"
                    name="article_video_max_mb"
                    value="{{ old('article_video_max_mb', $siteSettingsForm['article_video_max_mb']) }}"
                    class="input-field h-11"
                    min="1"
                    max="10240"
                    step="1"
                >
                <p class="mt-2 text-xs text-gray-500">影响文章编辑器和评论区的视频上传大小限制，默认 500MB，最大 10240MB。</p>
            </div>

            <section class="space-y-4 rounded-xl border border-slate-200 bg-slate-50/70 p-5">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">主题模式与时间段</h4>
                    <p class="mt-1 text-sm text-gray-500">管理员可固定白天 / 夜间模式，或设置在指定时间段自动切换。</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="theme_mode" class="mb-2 block text-sm font-medium text-gray-700">主题模式</label>
                        <select id="theme_mode" name="theme_mode" class="input-field h-11">
                            @foreach($themeModeOptions as $modeKey => $modeLabel)
                                <option value="{{ $modeKey }}" @selected(old('theme_mode', $siteSettingsForm['theme_mode']) === $modeKey)>{{ $modeLabel }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500">选择"自动"时会按下方时间段判断当前应该使用白天或夜间主题。</p>
                    </div>

                    <div class="grid gap-3">
                        <div>
                            <label for="theme_day_start" class="mb-2 block text-sm font-medium text-gray-700">白天模式开始</label>
                            <input id="theme_day_start" type="time" name="theme_day_start" value="{{ old('theme_day_start', $siteSettingsForm['theme_day_start']) }}" class="input-field h-11">
                        </div>
                        <div>
                            <label for="theme_night_start" class="mb-2 block text-sm font-medium text-gray-700">夜间模式开始</label>
                            <input id="theme_night_start" type="time" name="theme_night_start" value="{{ old('theme_night_start', $siteSettingsForm['theme_night_start']) }}" class="input-field h-11">
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-white bg-white px-4 py-3 text-xs text-gray-600">
                    说明：当"白天开始时间"晚于"夜间开始时间"时，系统会自动跨午夜计算，确保晚上与清晨仍处于夜间模式。
                </div>
            </section>

            <section class="space-y-4 rounded-xl border border-blue-100 bg-blue-50/40 p-5">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">允许用户使用的登录 / 注册方式</h4>
                    <p class="mt-1 text-sm text-gray-500">至少保留一种方式。拖拽调整顺序，从左到右显示。邮箱验证码与扫码方式可承担注册入口；邮箱密码主要用于已有账号登录。</p>
                </div>

                <div id="auth-methods-sortable" class="grid gap-3 md:grid-cols-2">
                    @php
                        $enabledMethods = old('auth_enabled_methods', $siteSettingsForm['auth_enabled_methods']);
                        $sortedMethods = [];
                        foreach ($enabledMethods as $methodKey) {
                            if (isset($authMethodOptions[$methodKey])) {
                                $sortedMethods[$methodKey] = $authMethodOptions[$methodKey];
                            }
                        }
                        foreach ($authMethodOptions as $methodKey => $methodOption) {
                            if (!in_array($methodKey, $enabledMethods, true)) {
                                $sortedMethods[$methodKey] = $methodOption;
                            }
                        }
                    @endphp
                    @foreach($sortedMethods as $methodKey => $methodOption)
                        <label class="auth-method-item flex items-start gap-3 rounded-lg border border-white bg-white p-4 shadow-sm cursor-move" draggable="true" data-method="{{ $methodKey }}">
                            <input type="checkbox" name="auth_enabled_methods[]" value="{{ $methodKey }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(in_array($methodKey, $enabledMethods, true))>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">{{ $methodOption['label'] }}</div>
                                <div class="mt-1 text-sm text-gray-500">{{ $methodOption['description'] }}</div>
                            </div>
                            <div class="text-gray-400 text-xl" title="拖拽排序">⋮⋮</div>
                        </label>
                    @endforeach
                </div>
            </section>

            <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                建议：如果你只是临时调整审查文案或开放方式，优先在这里修改；`app/config.toml` 仍保留作为启动默认值来源。
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">保存站点设置</button>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">数据备份与恢复</h3>
                <p class="mt-1 text-sm text-gray-500">备份会导出后台维护的核心设置与社区数据；恢复会覆盖当前核心数据并清理现有登录会话。</p>
            </div>
            <div class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">高风险操作</div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-blue-100 bg-blue-50 p-5">
                <h4 class="text-base font-semibold text-blue-900">备份核心数据</h4>
                <p class="mt-2 text-sm leading-6 text-blue-800">生成一个可离线保存的 <code class="rounded bg-white/80 px-1 py-0.5 text-xs text-blue-900">tar.gz</code> 文件，并立即下载到本地。该文件包含站点设置、邮件配置、用户资料、文章、评论、频道结构以及 DevTools 密钥等敏感信息，请务必妥善保管。</p>

                <div class="mt-4 rounded-lg border border-white/70 bg-white/80 p-4">
                    <p class="text-sm font-medium text-blue-900">当前将备份以下核心表</p>
                    <ul class="mt-3 space-y-2 text-sm text-blue-900">
                        @foreach ($backupTableSummaries as $tableSummary)
                            <li class="flex items-center justify-between gap-3">
                                <span class="font-mono text-xs uppercase tracking-wide text-blue-700">{{ $tableSummary['name'] }}</span>
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ $tableSummary['count'] }} 条</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-5">
                    <a href="{{ route('admin.site-settings.backup.download') }}" class="btn-primary inline-flex items-center justify-center">下载 tar.gz 备份</a>
                </div>
            </section>

            <section class="rounded-xl border border-red-100 bg-red-50 p-5">
                <h4 class="text-base font-semibold text-red-900">从备份恢复</h4>
                <p class="mt-2 text-sm leading-6 text-red-800">上传此前下载的 <code class="rounded bg-white/80 px-1 py-0.5 text-xs text-red-900">tar.gz</code> 备份文件即可恢复。恢复后，当前站点设置、邮件设置、用户、频道、文章、评论和 DevTools 密钥会被覆盖，所有登录会话也会被清理。</p>

                <form action="{{ route('admin.site-settings.backup.restore') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4" onsubmit="return confirm('确认使用该备份恢复核心数据吗？当前站点设置、用户、频道、文章、评论和登录会话都会被覆盖，且此操作不可撤销。')">
                    @csrf

                    <div>
                        <label for="backup_archive" class="mb-2 block text-sm font-medium text-red-900">备份文件</label>
                        <input id="backup_archive" type="file" name="backup_archive" accept=".tar.gz,application/gzip,application/x-gzip" class="input-field h-11 bg-white" required>
                        <p class="mt-2 text-xs text-red-700">请仅上传系统导出的 tar.gz 备份文件，避免使用手工修改过的压缩包。</p>
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">上传 tar.gz 恢复</button>
                </form>
            </section>
        </div>

        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            重要提醒：该备份文件包含敏感数据（例如密码哈希、双因子信息、SMTP 凭据与 API 密钥）。请仅在可信设备中保存，并避免通过不安全渠道传播。
        </div>
    </section>

    <script>
    (function() {
        const container = document.getElementById('auth-methods-sortable');
        if (!container) return;

        let draggedElement = null;

        container.addEventListener('dragstart', function(e) {
            if (e.target.classList.contains('auth-method-item')) {
                draggedElement = e.target;
                e.target.style.opacity = '0.5';
            }
        });

        container.addEventListener('dragend', function(e) {
            if (e.target.classList.contains('auth-method-item')) {
                e.target.style.opacity = '';
                draggedElement = null;
            }
        });

        container.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(container, e.clientY);
            if (afterElement == null) {
                container.appendChild(draggedElement);
            } else {
                container.insertBefore(draggedElement, afterElement);
            }
        });

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.auth-method-item:not([style*="opacity: 0.5"])')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;

                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
    })();
    </script>
@endsection
