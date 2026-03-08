<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteSettingsManager;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    public function edit(SiteSettingsManager $siteSettingsManager): View
    {
        return view('admin.site-settings.edit', [
            'pageTitle' => '站点设置',
            'siteSettingsForm' => $siteSettingsManager->formData(),
            'siteSettingsUsingOverrides' => $siteSettingsManager->usingOverrides(),
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
        ]);

        $siteSettingsManager->save($validated);
        $siteSettingsManager->applyConfiguredSettings();
        $staticPageBuilder->buildAll();

        return to_route('admin.site-settings.edit')->with('status', '站点设置已保存。');
    }
}
