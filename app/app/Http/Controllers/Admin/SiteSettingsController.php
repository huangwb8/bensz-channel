<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DataBackupManager;
use App\Support\SiteSettingsManager;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SiteSettingsController extends Controller
{
    public function edit(SiteSettingsManager $siteSettingsManager): View
    {
        return view('admin.site-settings.edit', [
            'pageTitle' => '站点设置',
            'siteSettingsForm' => $siteSettingsManager->siteFormData(),
            'siteSettingsUsingOverrides' => $siteSettingsManager->siteUsingOverrides(),
            'timezoneOptionGroups' => $siteSettingsManager->timezoneOptionGroups(),
            'themeModeOptions' => [
                'auto' => '自动（按时间段切换）',
                'light' => '固定白天模式',
                'dark' => '固定夜间模式',
            ],
            'authMethodOptions' => [
                'email_code' => ['label' => '邮箱 + 验证码', 'description' => '支持新用户注册，也可用于已有账号登录。'],
                'email_password' => ['label' => '邮箱 + 密码', 'description' => '适合已有账号的快速登录，不承担注册入口。'],
                'wechat_qr' => ['label' => '微信扫码', 'description' => '支持通过微信扫码登录/注册。'],
                'qq_qr' => ['label' => 'QQ扫码', 'description' => '支持通过 QQ 扫码登录/注册。'],
            ],
            'backupTableSummaries' => app(DataBackupManager::class)->backupTables(),
        ]);
    }

    public function downloadBackup(DataBackupManager $dataBackupManager): BinaryFileResponse|RedirectResponse
    {
        try {
            $archivePath = $dataBackupManager->createBackupArchive();

            return response()
                ->download($archivePath, 'bensz-channel-backup-'.now()->format('Ymd-His').'.tar.gz', [
                    'Content-Type' => 'application/gzip',
                ])
                ->deleteFileAfterSend(true);
        } catch (Throwable $exception) {
            report($exception);

            return to_route('admin.site-settings.edit')->withErrors('备份文件生成失败，请稍后重试。');
        }
    }

    public function restoreBackup(Request $request, DataBackupManager $dataBackupManager): RedirectResponse
    {
        $validated = $request->validate([
            'backup_archive' => ['required', 'file'],
        ]);

        /** @var UploadedFile $backupArchive */
        $backupArchive = $validated['backup_archive'];

        if (! $backupArchive->isValid()) {
            return to_route('admin.site-settings.edit')->withErrors('上传的备份文件无效，请重新选择后再试。');
        }

        try {
            $dataBackupManager->restoreFromArchive($backupArchive->getRealPath());

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')->with('status', '核心数据已从备份恢复，请重新登录并尽快核对站点配置。');
        } catch (Throwable $exception) {
            report($exception);

            return to_route('admin.site-settings.edit')->withErrors('恢复失败：无法识别该备份文件，或恢复过程中出现错误。');
        }
    }

    public function update(
        Request $request,
        SiteSettingsManager $siteSettingsManager,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', Rule::in($siteSettingsManager->availableTimezoneIdentifiers())],
            'article_image_max_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'article_video_max_mb' => ['required', 'integer', 'min:1', 'max:10240'],
            'theme_mode' => ['required', Rule::in(['auto', 'light', 'dark'])],
            'theme_day_start' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'theme_night_start' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'auth_enabled_methods' => ['required', 'array', 'min:1'],
            'auth_enabled_methods.*' => ['string', Rule::in($siteSettingsManager->availableAuthMethods())],
        ]);

        $siteSettingsManager->save($validated);
        $siteSettingsManager->applyConfiguredSettings();
        $staticPageBuilder->rebuildAll();

        return to_route('admin.site-settings.edit')->with('status', '站点设置已保存。');
    }
}
