<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteSettingsManager;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteSettingsController extends Controller
{
    public function edit(SiteSettingsManager $siteSettingsManager): View
    {
        return view('admin.site-settings.edit', [
            'pageTitle' => '站点设置',
            'siteSettingsForm' => $siteSettingsManager->formData(),
            'siteSettingsUsingOverrides' => $siteSettingsManager->usingOverrides(),
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
        ]);
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
            'cdn_asset_url' => ['nullable', 'url', 'max:255'],
            'theme_mode' => ['required', Rule::in(['auto', 'light', 'dark'])],
            'theme_day_start' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'theme_night_start' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'auth_enabled_methods' => ['required', 'array', 'min:1'],
            'auth_enabled_methods.*' => ['string', Rule::in($siteSettingsManager->availableAuthMethods())],
        ]);

        $siteSettingsManager->save($validated);
        $siteSettingsManager->applyConfiguredSettings();
        $staticPageBuilder->buildAll();

        return to_route('admin.site-settings.edit')->with('status', '站点设置已保存。');
    }
}
