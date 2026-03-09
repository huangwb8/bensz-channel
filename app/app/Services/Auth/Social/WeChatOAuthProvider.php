<?php

namespace App\Services\Auth\Social;

use App\Contracts\Auth\SocialOAuthProvider;
use App\Data\SocialAuthIdentity;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WeChatOAuthProvider implements SocialOAuthProvider
{
    public function __construct(private readonly HttpFactory $http) {}

    public function authorizationUrl(string $state): string
    {
        return 'https://open.weixin.qq.com/connect/qrconnect?'.http_build_query([
            'appid' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'snsapi_login',
            'state' => $state,
        ]).'#wechat_redirect';
    }

    public function resolveIdentity(string $code): SocialAuthIdentity
    {
        $tokenPayload = $this->request()
            ->get('https://api.weixin.qq.com/sns/oauth2/access_token', [
                'appid' => $this->clientId(),
                'secret' => $this->clientSecret(),
                'code' => $code,
                'grant_type' => 'authorization_code',
            ])
            ->throw()
            ->json();

        if (! is_array($tokenPayload) || filled(Arr::get($tokenPayload, 'errcode'))) {
            throw ValidationException::withMessages([
                'login_method' => '微信登录暂时不可用，请稍后重试。',
            ]);
        }

        $openid = trim((string) Arr::get($tokenPayload, 'openid'));
        $accessToken = trim((string) Arr::get($tokenPayload, 'access_token'));
        $unionId = $this->normalizeOptionalString(Arr::get($tokenPayload, 'unionid'));

        if ($openid === '' || $accessToken === '') {
            throw ValidationException::withMessages([
                'login_method' => '微信登录返回了无效身份，请稍后重试。',
            ]);
        }

        $profilePayload = [];

        try {
            $profilePayload = $this->request()
                ->get('https://api.weixin.qq.com/sns/userinfo', [
                    'access_token' => $accessToken,
                    'openid' => $openid,
                    'lang' => 'zh_CN',
                ])
                ->throw()
                ->json();
        } catch (\Throwable) {
            $profilePayload = [];
        }

        $name = $this->normalizeOptionalString(Arr::get($profilePayload, 'nickname'))
            ?? '微信用户'.Str::upper(substr(md5($unionId ?? $openid), 0, 6));

        $avatarUrl = $this->normalizeOptionalString(Arr::get($profilePayload, 'headimgurl'));

        return new SocialAuthIdentity(
            provider: 'wechat',
            providerUserId: $unionId ?? $openid,
            name: $name,
            avatarUrl: $avatarUrl,
            profileSnapshot: [
                'openid' => $openid,
                'unionid' => $unionId,
                'name' => $name,
                'avatar_url' => $avatarUrl,
                'raw' => $this->sanitizeRawProfile($profilePayload),
            ],
        );
    }

    private function request(): PendingRequest
    {
        return $this->http->acceptJson()->timeout((int) config('services.wechat.timeout', 8));
    }

    private function clientId(): string
    {
        return (string) config('services.wechat.client_id');
    }

    private function clientSecret(): string
    {
        return (string) config('services.wechat.client_secret');
    }

    private function redirectUri(): string
    {
        return (string) config('services.wechat.redirect');
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function sanitizeRawProfile(array $profilePayload): array
    {
        return Arr::only($profilePayload, [
            'openid',
            'unionid',
            'nickname',
            'sex',
            'province',
            'city',
            'country',
            'headimgurl',
            'privilege',
        ]);
    }
}
