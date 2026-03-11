<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CdnMode;
use App\Http\Controllers\Controller;
use App\Models\CdnSyncLog;
use App\Support\Cdn\CdnManager;
use App\Support\Cdn\CdnSyncService;
use App\Support\Cdn\Storage\StorageProviderFactory;
use App\Support\SiteSettingsManager;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CdnSettingsController extends Controller
{
    public function index(SiteSettingsManager $siteSettingsManager): View
    {
        return view('admin.cdn-settings.index', [
            'pageTitle' => 'CDN 设置',
            'cdnSettingsForm' => $siteSettingsManager->formData(),
            'cdnProviderOptions' => config('cdn.providers', []),
            'cdnSyncLogs' => class_exists(CdnSyncLog::class)
                ? CdnSyncLog::query()->latest('id')->limit(20)->get()
                : collect(),
            'cdnSyncDirectories' => config('cdn.sync.directories', []),
        ]);
    }

    public function update(
        Request $request,
        SiteSettingsManager $siteSettingsManager,
        CdnManager $cdnManager,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        $validated = $request->validate([
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

        $errors = $cdnManager->validateConfiguration($validated);

        if ($errors !== []) {
            return to_route('admin.cdn-settings.index')
                ->withErrors($errors)
                ->withInput();
        }

        $siteSettingsManager->save($validated);
        $siteSettingsManager->applyConfiguredSettings();
        $staticPageBuilder->rebuildAll();

        return to_route('admin.cdn-settings.index')->with('status', 'CDN 设置已保存。');
    }

    public function testConnection(CdnManager $cdnManager, StorageProviderFactory $storageProviderFactory): JsonResponse
    {
        $errors = $cdnManager->validateConfiguration();

        if ($errors !== []) {
            return response()->json([
                'status' => 'failed',
                'message' => implode('；', $errors),
            ], 422);
        }

        if ($cdnManager->getMode() === CdnMode::ORIGIN) {
            return response()->json([
                'status' => 'success',
                'message' => '回源型 CDN 配置校验通过。',
            ]);
        }

        $provider = $storageProviderFactory->make();
        $isValid = $provider->validateCredentials();

        return response()->json([
            'status' => $isValid ? 'success' : 'failed',
            'message' => $isValid ? '对象存储连接测试通过。' : '对象存储连接测试失败，请检查端点、桶与密钥。',
        ], $isValid ? 200 : 422);
    }

    public function diff(CdnSyncService $cdnSyncService): JsonResponse
    {
        $diff = $cdnSyncService->getDiff();

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
}
