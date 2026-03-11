<?php

namespace App\Http\Controllers;

use App\Support\PendingTwoFactorLogin;
use App\Support\SiteSettingsManager;
use App\Support\StaticPageBuilder;
use App\Support\TwoFactorAuthenticationManager;
use App\Support\UserAccountManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountSettingsController extends Controller
{
    public function edit(
        Request $request,
        SiteSettingsManager $siteSettingsManager,
        TwoFactorAuthenticationManager $twoFactorAuthenticationManager,
    ): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        return view('settings.account', [
            'pageTitle' => '账户设置',
            'passwordLoginEnabled' => in_array('email_password', $siteSettingsManager->enabledAuthMethods(), true),
            'userHasPassword' => filled($user->password),
            'twoFactorEnabled' => $twoFactorAuthenticationManager->hasEnabledTwoFactor($user),
            'twoFactorSetup' => $twoFactorAuthenticationManager->setupPayload($request, $user),
            'twoFactorRecoveryCodes' => array_values(array_filter((array) session('two_factor_recovery_codes', []), 'is_string')),
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

        $staticPageBuilder->rebuildAll();

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

    public function enableTwoFactor(Request $request, TwoFactorAuthenticationManager $twoFactorAuthenticationManager): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($twoFactorAuthenticationManager->hasEnabledTwoFactor($user)) {
            return to_route('settings.account.edit')->with('status', '两步验证已经开启。');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        if (! $twoFactorAuthenticationManager->verifyPendingSetupCode($request, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => '动态验证码不正确，请输入验证器当前显示的 6 位数字。',
            ]);
        }

        $recoveryCodes = $twoFactorAuthenticationManager->enable(
            $user,
            $twoFactorAuthenticationManager->pendingSetupSecret($request),
        );

        $twoFactorAuthenticationManager->clearPendingSetup($request);

        return to_route('settings.account.edit')
            ->with('status', '两步验证已开启，请立即保存恢复码。')
            ->with('two_factor_recovery_codes', $recoveryCodes);
    }

    public function disableTwoFactor(
        Request $request,
        TwoFactorAuthenticationManager $twoFactorAuthenticationManager,
        PendingTwoFactorLogin $pendingTwoFactorLogin,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:32'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ]);

        if (blank($validated['code'] ?? null) && blank($validated['recovery_code'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => '请输入动态验证码或恢复码。',
            ]);
        }

        if (! $twoFactorAuthenticationManager->verifyChallenge($user, $validated['code'] ?? null, $validated['recovery_code'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => '动态验证码或恢复码不正确。',
                'recovery_code' => '动态验证码或恢复码不正确。',
            ]);
        }

        $twoFactorAuthenticationManager->disable($user);
        $twoFactorAuthenticationManager->clearPendingSetup($request);
        $pendingTwoFactorLogin->clear($request);

        return to_route('settings.account.edit')->with('status', '两步验证已关闭。');
    }

    public function regenerateTwoFactorRecoveryCodes(
        Request $request,
        TwoFactorAuthenticationManager $twoFactorAuthenticationManager,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:32'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ]);

        if (blank($validated['code'] ?? null) && blank($validated['recovery_code'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => '请输入动态验证码或恢复码。',
            ]);
        }

        if (! $twoFactorAuthenticationManager->verifyChallenge($user, $validated['code'] ?? null, $validated['recovery_code'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => '动态验证码或恢复码不正确。',
                'recovery_code' => '动态验证码或恢复码不正确。',
            ]);
        }

        $recoveryCodes = $twoFactorAuthenticationManager->regenerateRecoveryCodes($user);

        return to_route('settings.account.edit')
            ->with('status', '恢复码已重新生成，请立即保存新的恢复码。')
            ->with('two_factor_recovery_codes', $recoveryCodes);
    }
}
