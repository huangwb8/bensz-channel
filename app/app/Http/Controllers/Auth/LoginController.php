<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Auth\OtpAuthGateway;
use App\Http\Controllers\Controller;
use App\Models\LoginCode;
use App\Models\QrLoginRequest;
use App\Services\Auth\LoginUserResolver;
use App\Support\QrLoginBroker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return to_route('home');
        }

        return view('auth.login', [
            'pageTitle' => '登录 / 注册',
            'providers' => config('community.auth.qr_providers'),
        ]);
    }

    public function sendCode(Request $request, OtpAuthGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in([LoginCode::CHANNEL_EMAIL, LoginCode::CHANNEL_PHONE])],
            'target' => ['required', 'string', 'max:120'],
        ]);

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
            ->withInput(['otp_channel' => $validated['channel'], 'otp_target' => $validated['target']])
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
    ): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in([LoginCode::CHANNEL_EMAIL, LoginCode::CHANNEL_PHONE])],
            'target' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'size:'.config('community.auth.otp_length')],
            'name' => ['nullable', 'string', 'max:40'],
        ]);

        $identity = $gateway->consume(
            $validated['channel'],
            $validated['target'],
            $validated['code'],
            $validated['name'] ?? null,
        );

        $user = $resolver->resolve($identity);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('status', '登录成功，欢迎回来。');
    }

    public function startQr(string $provider, QrLoginBroker $broker): RedirectResponse
    {
        abort_unless(in_array($provider, config('community.auth.qr_providers'), true), 404);

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

    public function status(Request $request, QrLoginRequest $qrLoginRequest): JsonResponse
    {
        if ($qrLoginRequest->isExpired()) {
            $qrLoginRequest->update(['status' => QrLoginRequest::STATUS_EXPIRED]);

            return response()->json(['status' => QrLoginRequest::STATUS_EXPIRED]);
        }

        if ($qrLoginRequest->status === QrLoginRequest::STATUS_APPROVED && $qrLoginRequest->approvedBy !== null) {
            Auth::login($qrLoginRequest->approvedBy, true);
            $request->session()->regenerate();

            $qrLoginRequest->update(['status' => QrLoginRequest::STATUS_CONSUMED]);

            return response()->json([
                'status' => QrLoginRequest::STATUS_CONSUMED,
                'redirect' => route('home'),
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

    private function shouldPreviewCode(): bool
    {
        return config('community.auth.preview_codes')
            && app()->environment(['local', 'testing']);
    }
}
