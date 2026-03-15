@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">CDN 设置</h2>
                <p class="mt-1 text-sm text-gray-500">统一管理回源型 CDN 与对象存储型 CDN，并在需要时执行资源同步。这是后台唯一的 CDN 配置入口。</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $cdnSettingsUsingOverrides ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">
                    {{ $cdnSettingsUsingOverrides ? '当前使用 CDN 后台覆盖配置' : '当前使用 config/config.toml 默认值' }}
                </div>
                <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $cdnSettingsForm['cdn_is_active'] ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }}">
                    {{ $cdnSettingsForm['cdn_is_active'] ? 'CDN 运行中' : 'CDN 未应用/已停止' }}
                </div>
                <div class="icon-action-group">
                    <x-icon-button :href="route('admin.site-settings.edit')" icon="save" label="站点设置" title="返回站点设置" />
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
        <div class="border-b border-gray-100 pb-4">
            <h3 class="text-lg font-semibold text-gray-900">模式与凭证</h3>
            <p class="mt-1 text-sm text-gray-500">建议优先使用回源型 CDN；如需将公开静态资源同步到对象存储，可切换到对象存储模式。保存仅更新草稿，不会立即影响线上运行状态。</p>
        </div>

        <form action="{{ route('admin.cdn-settings.update') }}" method="POST" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <label class="rounded-xl border border-gray-200 p-4">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="cdn_mode" value="origin" class="mt-1" @checked(old('cdn_mode', $cdnSettingsForm['cdn_mode']) === 'origin')>
                        <div>
                            <div class="font-medium text-gray-900">回源型 CDN</div>
                            <p class="mt-1 text-sm text-gray-500">CDN 节点从源站自动回源，适合 Cloudflare、DogeCloud CDN 等场景。</p>
                        </div>
                    </div>
                </label>
                <label class="rounded-xl border border-gray-200 p-4">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="cdn_mode" value="storage" class="mt-1" @checked(old('cdn_mode', $cdnSettingsForm['cdn_mode']) === 'storage')>
                        <div>
                            <div class="font-medium text-gray-900">对象存储型 CDN</div>
                            <p class="mt-1 text-sm text-gray-500">将 `public/` 下的公开静态资源同步到对象存储，适合多吉云、七牛云、又拍云等兼容 S3 的服务。</p>
                        </div>
                    </div>
                </label>
            </div>

            <div>
                <label for="cdn_asset_url" class="mb-2 block text-sm font-medium text-gray-700">公开资源域名</label>
                <input id="cdn_asset_url" type="url" name="cdn_asset_url" value="{{ old('cdn_asset_url', $cdnSettingsForm['cdn_asset_url']) }}" class="input-field h-11" placeholder="https://cdn.example.com">
                @error('cdn_asset_url')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-500">所有 `asset()` 生成的公开静态资源 URL 都会使用这个域名。</p>
            </div>

            <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 space-y-4">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">对象存储配置</h4>
                    <p class="mt-1 text-sm text-gray-500">仅在对象存储模式下生效。留空的 Access Key / Secret Key 会保留当前已保存的值。</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="cdn_storage_provider" class="mb-2 block text-sm font-medium text-gray-700">存储服务商</label>
                        <select id="cdn_storage_provider" name="cdn_storage_provider" class="input-field h-11">
                            @foreach($cdnProviderOptions as $providerKey => $provider)
                                <option value="{{ $providerKey }}" @selected(old('cdn_storage_provider', $cdnSettingsForm['cdn_storage_provider']) === $providerKey)>{{ $provider['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="cdn_storage_bucket" class="mb-2 block text-sm font-medium text-gray-700">存储桶</label>
                        <input id="cdn_storage_bucket" type="text" name="cdn_storage_bucket" value="{{ old('cdn_storage_bucket', $cdnSettingsForm['cdn_storage_bucket']) }}" class="input-field h-11" placeholder="bucket-name">
                        @error('cdn_storage_bucket')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="cdn_storage_region" class="mb-2 block text-sm font-medium text-gray-700">区域</label>
                        <input id="cdn_storage_region" type="text" name="cdn_storage_region" value="{{ old('cdn_storage_region', $cdnSettingsForm['cdn_storage_region']) }}" class="input-field h-11" placeholder="auto">
                        @error('cdn_storage_region')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="cdn_storage_endpoint" class="mb-2 block text-sm font-medium text-gray-700">端点</label>
                        <input id="cdn_storage_endpoint" type="url" name="cdn_storage_endpoint" value="{{ old('cdn_storage_endpoint', $cdnSettingsForm['cdn_storage_endpoint']) }}" class="input-field h-11" placeholder="https://oss.example.com">
                        @error('cdn_storage_endpoint')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="cdn_storage_access_key" class="mb-2 block text-sm font-medium text-gray-700">Access Key</label>
                        <input id="cdn_storage_access_key" type="text" name="cdn_storage_access_key" value="{{ old('cdn_storage_access_key', $cdnSettingsForm['cdn_storage_access_key']) }}" class="input-field h-11" placeholder="{{ $cdnSettingsForm['cdn_storage_access_key_masked'] ?? '留空则保持当前值' }}">
                    </div>
                    <div>
                        <label for="cdn_storage_secret_key" class="mb-2 block text-sm font-medium text-gray-700">Secret Key</label>
                        <input id="cdn_storage_secret_key" type="password" name="cdn_storage_secret_key" value="{{ old('cdn_storage_secret_key', $cdnSettingsForm['cdn_storage_secret_key']) }}" class="input-field h-11" placeholder="{{ $cdnSettingsForm['cdn_storage_secret_key_masked'] ?? '留空则保持当前值' }}">
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 space-y-4">
                <div class="cdn-runtime-panel">
                    <div class="cdn-runtime-panel-title">当前运行状态</div>
                    <div class="mt-2 space-y-1 text-xs sm:text-sm">
                        <p class="cdn-runtime-panel-line">状态：{{ $cdnSettingsForm['cdn_is_active'] ? '运行中' : '未应用或已停止' }}</p>
                        <p class="cdn-runtime-panel-line">模式：{{ $cdnSettingsForm['cdn_runtime_mode'] === 'storage' ? '对象存储型 CDN' : ($cdnSettingsForm['cdn_runtime_mode'] === 'origin' ? '回源型 CDN' : '未应用') }}</p>
                        <p class="cdn-runtime-panel-line">资源域名：{{ $cdnSettingsForm['cdn_runtime_asset_url'] ?: '未应用' }}</p>
                        <p class="cdn-runtime-panel-line">服务商：{{ $cdnSettingsForm['cdn_runtime_provider'] ?: '未应用' }}</p>
                        <p class="cdn-runtime-panel-line">同步：{{ $cdnSettingsForm['cdn_runtime_sync_enabled'] ? '已启用' : '未启用' }} / 构建后自动同步：{{ $cdnSettingsForm['cdn_runtime_sync_on_build'] ? '已启用' : '未启用' }}</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-base font-semibold text-gray-900">同步策略</h4>
                    <p class="mt-1 text-sm text-gray-500">当前默认同步目录：{{ implode('、', $cdnSyncDirectories) }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="rounded-lg border border-gray-200 bg-white p-4">
                        <input type="hidden" name="cdn_sync_enabled" value="0">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" name="cdn_sync_enabled" value="1" class="mt-1" @checked(old('cdn_sync_enabled', $cdnSettingsForm['cdn_sync_enabled']))>
                            <div>
                                <div class="font-medium text-gray-900">启用自动同步</div>
                                <p class="mt-1 text-sm text-gray-500">允许后台和命令行触发同步任务。</p>
                            </div>
                        </div>
                    </label>
                    <label class="rounded-lg border border-gray-200 bg-white p-4">
                        <input type="hidden" name="cdn_sync_on_build" value="0">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" name="cdn_sync_on_build" value="1" class="mt-1" @checked(old('cdn_sync_on_build', $cdnSettingsForm['cdn_sync_on_build']))>
                            <div>
                                <div class="font-medium text-gray-900">构建后自动同步</div>
                                <p class="mt-1 text-sm text-gray-500">静态页重建后自动派发 CDN 同步任务。</p>
                            </div>
                        </div>
                    </label>
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary inline-flex items-center justify-center">保存 CDN 设置</button>
                <button type="button" class="btn-secondary inline-flex items-center justify-center" data-cdn-apply>应用 CDN</button>
                <button type="button" class="btn-warning inline-flex items-center justify-center" data-cdn-stop>停止 CDN</button>
                <button type="button" class="btn-secondary inline-flex items-center justify-center" data-cdn-test>测试连接</button>
                <button type="button" class="btn-secondary inline-flex items-center justify-center" data-cdn-diff>查看差异</button>
                <button type="button" class="btn-secondary inline-flex items-center justify-center" data-cdn-sync>立即同步</button>
                <button type="button" class="btn-danger inline-flex items-center justify-center" data-cdn-clear>清空远程</button>
            </div>

            <div id="cdn-action-result" class="hidden rounded-lg border px-4 py-3 text-sm"></div>
        </form>
    </section>

    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
        <div class="border-b border-gray-100 pb-4">
            <h3 class="text-lg font-semibold text-gray-900">工作日志</h3>
            <p class="mt-1 text-sm text-gray-500">展示最近 50 次 CDN 相关工作，包含保存、测试、应用、停止、构建、同步、清理及失败原因。</p>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="px-3 py-2">时间</th>
                        <th class="px-3 py-2">工作类型</th>
                        <th class="px-3 py-2">状态</th>
                        <th class="px-3 py-2">文件数</th>
                        <th class="px-3 py-2">耗时</th>
                        <th class="px-3 py-2">摘要</th>
                        <th class="px-3 py-2">详情</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($cdnWorkLogs as $log)
                        <tr>
                            <td class="px-3 py-3 whitespace-nowrap">{{ optional($log->started_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td class="px-3 py-3">{{ $log->trigger }}</td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{
                                    in_array($log->status, ['success', 'queued'], true)
                                        ? 'bg-green-50 text-green-700 ring-1 ring-green-200'
                                        : ($log->status === 'skipped'
                                            ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                                            : 'bg-red-50 text-red-700 ring-1 ring-red-200')
                                }}">{{ $log->status }}</span>
                            </td>
                            <td class="px-3 py-3">{{ $log->uploaded_count }}/{{ $log->skipped_count }}/{{ $log->deleted_count }}</td>
                            <td class="px-3 py-3">{{ $log->duration_ms }} ms</td>
                            <td class="px-3 py-3">{{ $log->message }}</td>
                            <td class="px-3 py-3 max-w-xl">
                                @if(filled($log->details))
                                    <details class="group">
                                        <summary class="cursor-pointer text-sm text-blue-700">展开查看</summary>
                                        <pre class="mt-3 whitespace-pre-wrap rounded-lg bg-slate-50 p-3 text-xs text-slate-700">{{ $log->details }}</pre>
                                    </details>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-gray-500">暂无工作日志。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        (function () {
            const resultBox = document.getElementById('cdn-action-result');
            const form = document.querySelector('form[action="{{ route('admin.cdn-settings.update') }}"]');

            function showResult(type, message) {
                resultBox.classList.remove('hidden', 'border-green-200', 'bg-green-50', 'text-green-700', 'border-red-200', 'bg-red-50', 'text-red-700');
                resultBox.classList.add(type === 'success' ? 'border-green-200' : 'border-red-200');
                resultBox.classList.add(type === 'success' ? 'bg-green-50' : 'bg-red-50');
                resultBox.classList.add(type === 'success' ? 'text-green-700' : 'text-red-700');
                resultBox.textContent = message;
            }

            async function request(url, method, body = null) {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body,
                });

                const payload = await response.json();
                showResult(payload.status === 'success' ? 'success' : 'error', payload.message || '操作完成');
            }

            function formPayload() {
                return form ? new FormData(form) : new FormData();
            }

            document.querySelector('[data-cdn-apply]')?.addEventListener('click', () => request(@json(route('admin.cdn-settings.apply')), 'POST'));
            document.querySelector('[data-cdn-stop]')?.addEventListener('click', () => request(@json(route('admin.cdn-settings.stop')), 'POST'));
            document.querySelector('[data-cdn-test]')?.addEventListener('click', () => request(@json(route('admin.cdn-settings.test')), 'POST', formPayload()));
            document.querySelector('[data-cdn-diff]')?.addEventListener('click', async () => {
                const response = await fetch(@json(route('admin.cdn-settings.diff')), { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                if (!response.ok) {
                    showResult('error', payload.message || '获取差异失败');
                    return;
                }
                showResult('success', `待上传 ${payload.upload_count}，待删除 ${payload.delete_count}，已同步 ${payload.skip_count}`);
            });
            document.querySelector('[data-cdn-sync]')?.addEventListener('click', () => request(@json(route('admin.cdn-settings.sync')), 'POST'));
            document.querySelector('[data-cdn-clear]')?.addEventListener('click', () => {
                if (!confirm('确认清空远程对象存储中的已同步文件吗？此操作不可撤销。')) {
                    return;
                }

                request(@json(route('admin.cdn-settings.remote.clear')), 'DELETE');
            });
        })();
    </script>
@endsection
