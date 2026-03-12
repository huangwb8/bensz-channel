<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CdnMode;
use App\Http\Controllers\Controller;
use App\Models\CdnSyncLog;
use App\Support\Cdn\CdnManager;
use App\Support\Cdn\CdnSyncService;
use App\Support\Cdn\CdnWorkLogManager;
use App\Support\Cdn\Storage\StorageProviderFactory;
use App\Support\SiteSettingsManager;
use App\Support\StaticPageBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CdnSettingsController extends Controller
{
    public function index(SiteSettingsManager $siteSettingsManager): View
    {
        return view('admin.cdn-settings.index', [
            'pageTitle' => 'CDN 设置',
            'cdnSettingsForm' => $siteSettingsManager->cdnFormData(),
            'cdnSettingsUsingOverrides' => $siteSettingsManager->cdnUsingOverrides(),
            'cdnProviderOptions' => config('cdn.providers', []),
            'cdnWorkLogs' => class_exists(CdnSyncLog::class)
                ? CdnSyncLog::query()->latest('id')->limit(50)->get()
                : collect(),
            'cdnSyncDirectories' => config('cdn.sync.directories', []),
        ]);
    }

    public function update(
        Request $request,
        SiteSettingsManager $siteSettingsManager,
        CdnManager $cdnManager,
        CdnWorkLogManager $workLogManager,
    ): RedirectResponse {
        $startedAt = CarbonImmutable::now();
        $validator = $this->makeValidator($request);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $this->recordDraftLog($workLogManager, 'save', 'failed', [], $errors, [
                '工作类型：保存 CDN 草稿配置',
                '失败原因：'.implode('；', $errors),
            ], $startedAt);

            return to_route('admin.cdn-settings.index')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $configuration = $cdnManager->draftConfiguration($validated);
        $errors = $cdnManager->validateConfiguration($validated);

        if ($errors !== []) {
            $this->recordDraftLog($workLogManager, 'save', 'failed', $configuration, $errors, [
                '工作类型：保存 CDN 草稿配置',
                '失败原因：'.implode('；', $errors),
            ], $startedAt);

            return to_route('admin.cdn-settings.index')
                ->withErrors($errors)
                ->withInput();
        }

        $siteSettingsManager->save($validated);

        $this->recordDraftLog($workLogManager, 'save', 'success', $configuration, [], [
            '工作类型：保存 CDN 草稿配置',
            '结果：配置已保存，但尚未应用到运行中的站点。',
            '下一步：可继续测试连接、查看差异，然后手动点击“应用 CDN”。',
        ], $startedAt, 'CDN 配置已保存，尚未应用。');

        return to_route('admin.cdn-settings.index')->with('status', 'CDN 设置已保存，尚未应用。');
    }

    public function testConnection(
        Request $request,
        CdnManager $cdnManager,
        StorageProviderFactory $storageProviderFactory,
        CdnWorkLogManager $workLogManager,
    ): JsonResponse {
        $startedAt = CarbonImmutable::now();
        $validator = $this->makeValidator($request);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $payload = $this->makeActionPayload(
                'test',
                'failed',
                [],
                $errors,
                [
                    '工作类型：测试 CDN 连接',
                    '失败原因：'.implode('；', $errors),
                ],
                $startedAt,
            );
            $workLogManager->record($payload);

            return response()->json([
                'status' => 'failed',
                'message' => implode('；', $errors),
            ], 422);
        }

        $validated = $validator->validated();
        $configuration = $cdnManager->draftConfiguration($validated);
        $errors = $cdnManager->validateConfiguration($validated);

        if ($errors !== []) {
            $payload = $this->makeActionPayload(
                'test',
                'failed',
                $configuration,
                $errors,
                [
                    '工作类型：测试 CDN 连接',
                    '失败原因：'.implode('；', $errors),
                ],
                $startedAt,
            );
            $workLogManager->record($payload);

            return response()->json([
                'status' => 'failed',
                'message' => implode('；', $errors),
            ], 422);
        }

        if (($configuration['mode'] ?? null) === CdnMode::ORIGIN->value) {
            $payload = $this->makeActionPayload(
                'test',
                'success',
                $configuration,
                [],
                [
                    '工作类型：测试 CDN 连接',
                    '当前模式：回源型 CDN',
                    '结果：配置校验通过，应用后将直接使用公开资源域名。',
                ],
                $startedAt,
                '回源型 CDN 配置校验通过。',
            );
            $workLogManager->record($payload);

            return response()->json([
                'status' => 'success',
                'message' => '回源型 CDN 配置校验通过。',
            ]);
        }

        $provider = $storageProviderFactory->makeFromConfiguration(
            $cdnManager->storageConfigurationFromResolved($configuration),
        );
        $result = $provider->testConnection();
        $payload = $this->makeActionPayload(
            'test',
            $result->successful ? 'success' : 'failed',
            $configuration,
            $result->successful ? [] : [$result->message],
            [
                '工作类型：测试 CDN 连接',
                $result->details,
            ],
            $startedAt,
            $result->message,
            $result->context,
        );
        $workLogManager->record($payload);

        return response()->json([
            'status' => $result->successful ? 'success' : 'failed',
            'message' => $result->message,
        ], $result->successful ? 200 : 422);
    }

    public function apply(
        CdnManager $cdnManager,
        SiteSettingsManager $siteSettingsManager,
        StorageProviderFactory $storageProviderFactory,
        StaticPageBuilder $staticPageBuilder,
        CdnWorkLogManager $workLogManager,
    ): JsonResponse {
        $startedAt = CarbonImmutable::now();
        $configuration = $cdnManager->draftConfiguration();
        $errors = $cdnManager->validateConfiguration();
        $details = [
            '工作类型：应用 CDN',
            '当前草稿模式：'.$this->modeLabel($configuration['mode'] ?? null),
        ];

        if (($configuration['mode'] ?? null) === CdnMode::STORAGE->value && $errors === []) {
            $connectionResult = $storageProviderFactory
                ->makeFromConfiguration($cdnManager->storageConfigurationFromResolved($configuration))
                ->testConnection();

            $details[] = $connectionResult->details;

            if (! $connectionResult->successful) {
                $errors[] = $connectionResult->message;
            }
        }

        if ($errors !== []) {
            $payload = $this->makeActionPayload(
                'apply',
                'failed',
                $configuration,
                $errors,
                [
                    ...$details,
                    '失败原因：'.implode('；', $errors),
                ],
                $startedAt,
            );
            $workLogManager->record($payload);

            return response()->json([
                'status' => 'failed',
                'message' => implode('；', $errors),
            ], 422);
        }

        $siteSettingsManager->activateCdn($configuration);
        $siteSettingsManager->applyConfiguredSettings();
        $staticPageBuilder->rebuildAll();

        $message = 'CDN 已应用，静态页重建流程已触发。';
        $payload = $this->makeActionPayload(
            'apply',
            'success',
            $configuration,
            [],
            [
                ...$details,
                '结果：CDN 已切换到运行状态。',
                '静态重建：已触发，用于刷新页面中的资源地址。',
            ],
            $startedAt,
            $message,
        );
        $workLogManager->record($payload);

        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    public function stop(
        SiteSettingsManager $siteSettingsManager,
        StaticPageBuilder $staticPageBuilder,
        CdnWorkLogManager $workLogManager,
    ): JsonResponse {
        $startedAt = CarbonImmutable::now();
        $siteSettingsManager->deactivateCdn();
        $siteSettingsManager->applyConfiguredSettings();
        $staticPageBuilder->rebuildAll();

        $payload = $this->makeActionPayload(
            'stop',
            'success',
            [],
            [],
            [
                '工作类型：停止 CDN',
                '结果：运行中的 CDN 已停止，已恢复到基础配置。',
                '静态重建：已触发，用于移除页面中的 CDN 资源地址。',
            ],
            $startedAt,
            'CDN 已停止，静态页重建流程已触发。',
        );
        $workLogManager->record($payload);

        return response()->json([
            'status' => 'success',
            'message' => 'CDN 已停止，静态页重建流程已触发。',
        ]);
    }

    public function diff(CdnManager $cdnManager, CdnSyncService $cdnSyncService, CdnWorkLogManager $workLogManager): JsonResponse
    {
        $startedAt = CarbonImmutable::now();
        $configuration = $cdnManager->draftConfiguration();

        if (($configuration['mode'] ?? null) !== CdnMode::STORAGE->value) {
            $payload = $this->makeActionPayload(
                'diff',
                'failed',
                $configuration,
                ['仅对象存储模式支持查看差异。'],
                [
                    '工作类型：查看同步差异',
                    '失败原因：当前草稿不是对象存储模式。',
                ],
                $startedAt,
            );
            $workLogManager->record($payload);

            return response()->json([
                'status' => 'failed',
                'message' => '仅对象存储模式支持查看差异。',
                'upload_count' => 0,
                'skip_count' => 0,
                'delete_count' => 0,
                'diff' => ['upload' => [], 'skip' => [], 'delete' => []],
            ], 422);
        }

        $diff = $cdnSyncService->getDiff();
        $payload = $this->makeActionPayload(
            'diff',
            'success',
            $configuration,
            [],
            [
                '工作类型：查看同步差异',
                sprintf('待上传 %d 个，已同步 %d 个，待删除 %d 个。', count($diff['upload']), count($diff['skip']), count($diff['delete'])),
                $workLogManager->summarizePaths('待上传文件', $diff['upload']),
                $workLogManager->summarizePaths('待删除文件', $diff['delete']),
            ],
            $startedAt,
            'CDN 同步差异已生成。',
            ['diff' => $diff],
            count($diff['upload']),
            count($diff['skip']),
            count($diff['delete']),
        );
        $workLogManager->record($payload);

        return response()->json([
            'status' => 'success',
            'upload_count' => count($diff['upload']),
            'skip_count' => count($diff['skip']),
            'delete_count' => count($diff['delete']),
            'diff' => $diff,
        ]);
    }

    public function sync(CdnSyncService $cdnSyncService): JsonResponse
    {
        $result = $cdnSyncService->syncAll('manual');

        return response()->json([
            'status' => $result->successful() ? 'success' : 'failed',
            'message' => $result->message(),
            'result' => $result->toLogPayload(),
        ], $result->successful() ? 200 : 422);
    }

    public function clearRemote(CdnSyncService $cdnSyncService): JsonResponse
    {
        $result = $cdnSyncService->clearRemote();

        return response()->json([
            'status' => $result->successful() ? 'success' : 'failed',
            'message' => $result->message(),
            'result' => $result->toLogPayload(),
        ], $result->successful() ? 200 : 422);
    }

    private function makeValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'cdn_mode' => ['required', Rule::in(array_column(CdnMode::cases(), 'value'))],
            'cdn_asset_url' => ['nullable', 'url', 'max:255'],
            'cdn_storage_provider' => ['nullable', 'string', 'max:50'],
            'cdn_storage_access_key' => ['nullable', 'string', 'max:255'],
            'cdn_storage_secret_key' => ['nullable', 'string', 'max:255'],
            'cdn_storage_bucket' => ['nullable', 'string', 'max:100'],
            'cdn_storage_region' => ['nullable', 'string', 'max:100'],
            'cdn_storage_endpoint' => ['nullable', 'url', 'max:255'],
            'cdn_sync_enabled' => ['required', 'boolean'],
            'cdn_sync_on_build' => ['required', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $details
     */
    private function recordDraftLog(
        CdnWorkLogManager $workLogManager,
        string $trigger,
        string $status,
        array $configuration,
        array $errors,
        array $details,
        CarbonImmutable $startedAt,
        ?string $message = null,
    ): void {
        $workLogManager->record(
            $this->makeActionPayload($trigger, $status, $configuration, $errors, $details, $startedAt, $message),
        );
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $details
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function makeActionPayload(
        string $trigger,
        string $status,
        array $configuration,
        array $errors,
        array $details,
        CarbonImmutable $startedAt,
        ?string $message = null,
        array $context = [],
        int $uploadedCount = 0,
        int $skippedCount = 0,
        int $deletedCount = 0,
    ): array {
        $finishedAt = CarbonImmutable::now();

        return [
            'trigger' => $trigger,
            'status' => $status,
            'mode' => (string) ($configuration['mode'] ?? CdnMode::ORIGIN->value),
            'provider' => $configuration['storage_provider'] ?? null,
            'uploaded_count' => $uploadedCount,
            'skipped_count' => $skippedCount,
            'deleted_count' => $deletedCount,
            'total_count' => $uploadedCount + $skippedCount + $deletedCount,
            'duration_ms' => max(0, $finishedAt->diffInMilliseconds($startedAt)),
            'message' => $message ?? ($errors === [] ? 'CDN 工作已完成。' : implode('；', $errors)),
            'details' => app(CdnWorkLogManager::class)->buildDetails([
                ...$details,
                '配置摘要：'.json_encode($this->configurationSummary($configuration), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]),
            'context' => array_merge($context, [
                'configuration' => $this->configurationSummary($configuration),
                'errors' => $errors,
            ]),
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array<string, mixed>
     */
    private function configurationSummary(array $configuration): array
    {
        return [
            'mode' => $configuration['mode'] ?? null,
            'asset_url' => $configuration['asset_url'] ?? null,
            'storage_provider' => $configuration['storage_provider'] ?? null,
            'storage_bucket' => $configuration['storage_bucket'] ?? null,
            'storage_region' => $configuration['storage_region'] ?? null,
            'storage_endpoint' => $configuration['storage_endpoint'] ?? null,
            'storage_access_key' => $this->maskSecret($configuration['storage_access_key'] ?? null),
            'storage_secret_key' => $this->maskSecret($configuration['storage_secret_key'] ?? null),
            'sync_enabled' => (bool) ($configuration['sync_enabled'] ?? false),
            'sync_on_build' => (bool) ($configuration['sync_on_build'] ?? false),
        ];
    }

    private function modeLabel(mixed $mode): string
    {
        return match ($mode) {
            CdnMode::STORAGE->value => '对象存储型 CDN',
            CdnMode::ORIGIN->value => '回源型 CDN',
            default => '未配置',
        };
    }

    private function maskSecret(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);
        $length = mb_strlen($trimmed);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return mb_substr($trimmed, 0, 2).str_repeat('*', max(4, $length - 4)).mb_substr($trimmed, -2);
    }
}
