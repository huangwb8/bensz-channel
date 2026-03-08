<?php

namespace App\Http\Controllers;

use App\Support\SiteSettingsManager;
use App\Support\StaticPageBuilder;
use App\Support\UserAccountManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountSettingsController extends Controller
{
    public function edit(Request $request, SiteSettingsManager $siteSettingsManager): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        return view('settings.account', [
            'pageTitle' => '账户设置',
            'passwordLoginEnabled' => in_array('email_password', $siteSettingsManager->enabledAuthMethods(), true),
            'userHasPassword' => filled($user->password),
        ]);
    }

    public function updateProfile(
        Request $request,
        UserAccountManager $userAccountManager,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $request->merge($userAccountManager->normalizeProfileInput($request->only([
            'name',
            'email',
            'phone',
            'avatar_url',
            'bio',
        ])));

        $validated = $request->validate($userAccountManager->profileValidationRules($user));

        $userAccountManager->assertHasLoginIdentifier($validated);
        $userAccountManager->fillProfile($user, $validated);
        $user->save();

        $staticPageBuilder->buildAll();

        return to_route('settings.account.edit')->with('status', '账户资料已更新。');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if (blank($user->email)) {
            throw ValidationException::withMessages([
                'email' => '请先绑定邮箱，再设置邮箱密码登录。',
            ]);
        }

        $rules = [
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ];

        if (filled($user->password)) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($rules);

        $alreadyHadPassword = filled($user->password);

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        $request->session()->regenerate();

        return to_route('settings.account.edit')->with('status', $alreadyHadPassword ? '登录密码已更新。' : '登录密码已设置。');
    }
}
