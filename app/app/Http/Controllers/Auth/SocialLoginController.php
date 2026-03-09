<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAccountResolver;
use App\Services\Auth\SocialOAuthManager;
use App\Support\QrLoginBroker;
use App\Support\SiteSettingsManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class SocialLoginController extends Controller
{
    public function redirect(
        string $provider,
        SiteSettingsManager $siteSettingsManager,
        SocialOAuthManager $socialOAuthManager,
        QrLoginBroker $qrLoginBroker,
    ): RedirectResponse {
        abort_unless(in_array($provider, $siteSettingsManager->enabledQrProviders(), true), 404);

        if ($socialOAuthManager->mode($provider) === 'demo') {
            return to_route('auth.qr.show', $qrLoginBroker->create($provider));
        }

        if (! $socialOAuthManager->isReadyForOAuth($provider)) {
            return to_route('login')->withErrors([
                'login_method' => '当前登录方式尚未完成平台配置，请先完善开放平台参数。',
            ]);
        }

        $state = Str::random(40);
        session()->put("social_oauth_state.{$provider}", $state);

        return redirect()->away($socialOAuthManager->driver($provider)->authorizationUrl($state));
    }

    public function callback(
        Request $request,
        string $provider,
        SiteSettingsManager $siteSettingsManager,
        SocialOAuthManager $socialOAuthManager,
        SocialAccountResolver $socialAccountResolver,
    ): RedirectResponse {
        abort_unless(in_array($provider, $siteSettingsManager->enabledQrProviders(), true), 404);
        abort_unless($socialOAuthManager->isReadyForOAuth($provider), 404);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'error' => ['nullable', 'string', 'max:255'],
            'error_description' => ['nullable', 'string', 'max:255'],
        ]);

        $expectedState = (string) $request->session()->pull("social_oauth_state.{$provider}", '');

        if ($expectedState === '' || ! hash_equals($expectedState, (string) ($validated['state'] ?? ''))) {
            return to_route('login')->withErrors([
                'login_method' => '登录校验失败，请重新发起扫码登录。',
            ]);
        }

        if (filled($validated['error'] ?? null)) {
            return to_route('login')->withErrors([
                'login_method' => '扫码授权已取消或被拒绝，请重新尝试。',
            ]);
        }

        if (! filled($validated['code'] ?? null)) {
            return to_route('login')->withErrors([
                'login_method' => '未收到授权凭证，请重新尝试扫码登录。',
            ]);
        }

        try {
            $identity = $socialOAuthManager->driver($provider)->resolveIdentity((string) $validated['code']);
            $user = $socialAccountResolver->resolve($identity);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return to_route('login')->withErrors($exception->errors());
        } catch (Throwable $exception) {
            if (! app()->runningUnitTests()) {
                report($exception);
            }

            return to_route('login')->withErrors([
                'login_method' => '登录服务暂时不可用，请稍后重试。',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('status', '登录成功，欢迎回来。');
    }
}
