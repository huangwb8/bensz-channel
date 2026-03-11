<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Auth\OtpAuthGateway;
use App\Http\Controllers\Controller;
use App\Models\LoginCode;
use App\Models\QrLoginRequest;
use App\Services\Auth\LoginUserResolver;
use App\Services\Auth\SocialOAuthManager;
use App\Support\PendingTwoFactorLogin;
use App\Support\QrLoginBroker;
use App\Support\SiteSettingsManager;
use App\Support\TwoFactorAuthenticationManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return to_route('home');
        }

        $siteSettingsManager = app(SiteSettingsManager::class);

        return view('auth.login', [
            'pageTitle' => '登录 / 注册',
            'providers' => $siteSettingsManager->enabledQrProviders(),
            'enabledAuthMethods' => $this->enabledAuthMethods(),
            'socialProviders' => [
                'wechat' => app(SocialOAuthManager::class)->presentation('wechat'),
                'qq' => app(SocialOAuthManager::class)->presentation('qq'),
            ],
        ]);
    }

    public function sendCode(Request $request, OtpAuthGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in([LoginCode::CHANNEL_EMAIL, LoginCode::CHANNEL_PHONE])],
            'target' => ['required', 'string', 'max:120'],
            'login_method' => ['nullable', 'string', 'max:32'],
        ]);

        if ($validated['channel'] === LoginCode::CHANNEL_EMAIL) {
            $this->ensureAuthMethodEnabled('email_code');
        }

        $request->validate([
            'target' => $validated['channel'] === LoginCode::CHANNEL_EMAIL
                ? ['required', 'email', 'max:120']
                : ['required', 'regex:/^\+?[0-9\-\s]{6,24}$/'],
        ]);

        $previewCode = $gateway->issue($validated['channel'], $validated['target']);

        $message = $validated['channel'] === LoginCode::CHANNEL_EMAIL
            ? '验证码已发送到邮箱，请查收后完成验证。'
            : '验证码已发送到手机渠道，请查收后完成验证。';

        $redirect = back()
            ->withInput([
                'login_method' => $validated['login_method'] ?? 'email-code',
                'otp_channel' => $validated['channel'],
                'otp_target' => $validated['target'],
            ])
            ->with('status', $message);

        if ($this->shouldPreviewCode() && filled($previewCode)) {
            $redirect->with('otp_preview', $previewCode);
        }

        return $redirect;
    }

    public function verifyCode(
        Request $request,
        OtpAuthGateway $gateway,
        LoginUserResolver $resolver,
        PendingTwoFactorLogin $pendingTwoFactorLogin,
    ): RedirectResponse {
        $validated = $request->validate([
            'channel' => ['required', Rule::in([LoginCode::CHANNEL_EMAIL, LoginCode::CHANNEL_PHONE])],
            'target' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'size:'.config('community.auth.otp_length')],
            'name' => ['nullable', 'string', 'max:40'],
        ]);

        if ($validated['channel'] === LoginCode::CHANNEL_EMAIL) {
            $this->ensureAuthMethodEnabled('email_code');
        }

        $identity = $gateway->consume(
            $validated['channel'],
            $validated['target'],
            $validated['code'],
            $validated['name'] ?? null,
        );

        $user = $resolver->resolve($identity);
        $this->ensureNotBanned($user);

        if ($pendingTwoFactorLogin->start($request, $user, true)) {
            return to_route('auth.two-factor.challenge');
        }

        return redirect()->intended(route('home'))->with('status', '登录成功，欢迎回来。');
    }

    public function loginWithPassword(Request $request, PendingTwoFactorLogin $pendingTwoFactorLogin): RedirectResponse
    {
        $this->ensureAuthMethodEnabled('email_password');

        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $guard = Auth::guard();

        if (! $guard->validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => '邮箱或密码不正确。',
            ]);
        }

        $user = $guard->getProvider()->retrieveByCredentials($credentials);

        if (! $user instanceof \App\Models\User) {
            throw ValidationException::withMessages([
                'email' => '邮箱或密码不正确。',
            ]);
        }

        $this->ensureNotBanned($user);

        if ($pendingTwoFactorLogin->start($request, $user, true)) {
            return to_route('auth.two-factor.challenge');
        }

        return redirect()->intended(route('home'))->with('status', '登录成功，欢迎回来。');
    }

    public function showTwoFactorChallenge(Request $request, PendingTwoFactorLogin $pendingTwoFactorLogin): View|RedirectResponse
    {
        if (Auth::check()) {
            return to_route('home');
        }

        $user = $pendingTwoFactorLogin->pendingUser($request);

        if ($user === null) {
            return to_route('login')->withErrors([
                'login_method' => '登录状态已失效，请重新登录。',
            ]);
        }

        if ($user->isBanned()) {
            $pendingTwoFactorLogin->clear($request);

            return to_route('login')->withErrors([
                'login_method' => $user->activeBanMessage() ?? '该账号已被封禁，请联系管理员。',
            ]);
        }

        return view('auth.two-factor', [
            'pageTitle' => '两步验证',
        ]);
    }

    public function verifyTwoFactor(
        Request $request,
        PendingTwoFactorLogin $pendingTwoFactorLogin,
        TwoFactorAuthenticationManager $twoFactorAuthenticationManager,
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:32'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ]);

        if (blank($validated['code'] ?? null) && blank($validated['recovery_code'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => '请输入动态验证码或恢复码。',
            ]);
        }

        $user = $pendingTwoFactorLogin->pendingUser($request);

        if ($user === null) {
            return to_route('login')->withErrors([
                'login_method' => '登录状态已失效，请重新登录。',
            ]);
        }

        if ($user->isBanned()) {
            $pendingTwoFactorLogin->clear($request);

            return to_route('login')->withErrors([
                'login_method' => $user->activeBanMessage() ?? '该账号已被封禁，请联系管理员。',
            ]);
        }

        if (! $twoFactorAuthenticationManager->verifyChallenge($user, $validated['code'] ?? null, $validated['recovery_code'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => '动态验证码或恢复码不正确。',
                'recovery_code' => '动态验证码或恢复码不正确。',
            ]);
        }

        $pendingTwoFactorLogin->complete($request);

        return redirect()->intended(route('home'))->with('status', '登录成功，欢迎回来。');
    }

    public function startQr(string $provider, QrLoginBroker $broker): RedirectResponse
    {
        abort_unless(in_array($provider, app(SiteSettingsManager::class)->enabledQrProviders(), true), 404);
        abort_unless(app(SocialOAuthManager::class)->mode($provider) === 'demo', 404);

        return to_route('auth.qr.show', $broker->create($provider));
    }

    public function showQr(QrLoginRequest $qrLoginRequest): View
    {
        return view('auth.qr', [
            'pageTitle' => $this->providerLabel($qrLoginRequest->provider).'扫码登录',
            'qrLoginRequest' => $qrLoginRequest,
            'providerLabel' => $this->providerLabel($qrLoginRequest->provider),
            'approvalUrl' => route('auth.qr.approve.show', [$qrLoginRequest->provider, $qrLoginRequest]),
        ]);
    }

    public function status(Request $request, QrLoginRequest $qrLoginRequest, PendingTwoFactorLogin $pendingTwoFactorLogin): JsonResponse
    {
        if ($qrLoginRequest->isExpired()) {
            $qrLoginRequest->update(['status' => QrLoginRequest::STATUS_EXPIRED]);

            return response()->json(['status' => QrLoginRequest::STATUS_EXPIRED]);
        }

        if ($qrLoginRequest->status === QrLoginRequest::STATUS_APPROVED && $qrLoginRequest->approvedBy !== null) {
            if ($qrLoginRequest->approvedBy->isBanned()) {
                $qrLoginRequest->update(['status' => QrLoginRequest::STATUS_CONSUMED]);
                $request->session()->flash('status', $qrLoginRequest->approvedBy->activeBanMessage() ?? '该账号已被封禁，请联系管理员。');

                return response()->json([
                    'status' => QrLoginRequest::STATUS_CONSUMED,
                    'redirect' => route('login'),
                ]);
            }

            $requiresTwoFactorChallenge = $pendingTwoFactorLogin->start($request, $qrLoginRequest->approvedBy, true);

            $qrLoginRequest->update(['status' => QrLoginRequest::STATUS_CONSUMED]);

            return response()->json([
                'status' => QrLoginRequest::STATUS_CONSUMED,
                'redirect' => $requiresTwoFactorChallenge ? route('auth.two-factor.challenge') : route('home'),
            ]);
        }

        return response()->json(['status' => $qrLoginRequest->status]);
    }

    public function showApproval(string $provider, QrLoginRequest $qrLoginRequest): View
    {
        abort_unless($provider === $qrLoginRequest->provider, 404);

        return view('auth.approve', [
            'pageTitle' => $this->providerLabel($provider).'授权确认',
            'qrLoginRequest' => $qrLoginRequest,
            'providerLabel' => $this->providerLabel($provider),
        ]);
    }

    public function approve(
        Request $request,
        string $provider,
        QrLoginRequest $qrLoginRequest,
        QrLoginBroker $broker,
    ): View {
        abort_unless($provider === $qrLoginRequest->provider, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'provider_user_id' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $user = $broker->approve($qrLoginRequest, $validated);

        return view('auth.approved', [
            'pageTitle' => $this->providerLabel($provider).'扫码成功',
            'providerLabel' => $this->providerLabel($provider),
            'user' => $user,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home')->with('status', '你已安全退出。');
    }

    private function providerLabel(string $provider): string
    {
        return Arr::get([
            'wechat' => '微信',
            'qq' => 'QQ',
        ], $provider, strtoupper($provider));
    }

    private function enabledAuthMethods(): array
    {
        return app(SiteSettingsManager::class)->enabledAuthMethods();
    }

    private function ensureAuthMethodEnabled(string $method): void
    {
        if (in_array($method, $this->enabledAuthMethods(), true)) {
            return;
        }

        throw ValidationException::withMessages([
            'login_method' => '当前未开放该登录方式，请联系管理员。',
        ]);
    }

    private function ensureNotBanned(\App\Models\User $user): void
    {
        if (! $user->isBanned()) {
            return;
        }

        throw ValidationException::withMessages([
            'login_method' => $user->activeBanMessage() ?? '该账号已被封禁，请联系管理员。',
        ]);
    }

    private function shouldPreviewCode(): bool
    {
        return config('community.auth.preview_codes')
            && app()->environment(['local', 'testing']);
    }
}
