<?php

namespace App\Services\Auth\Social;

use App\Contracts\Auth\SocialOAuthProvider;
use App\Data\SocialAuthIdentity;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QqOAuthProvider implements SocialOAuthProvider
{
    public function __construct(private readonly HttpFactory $http) {}

    public function authorizationUrl(string $state): string
    {
        return 'https://graph.qq.com/oauth2.0/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'scope' => 'get_user_info',
            'display' => 'pc',
        ]);
    }

    public function resolveIdentity(string $code): SocialAuthIdentity
    {
        $tokenResponse = $this->request()
            ->get('https://graph.qq.com/oauth2.0/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'fmt' => 'json',
            ])
            ->throw();

        $tokenPayload = $tokenResponse->json();

        if (! is_array($tokenPayload) || filled(Arr::get($tokenPayload, 'error'))) {
            parse_str((string) $tokenResponse->body(), $fallbackPayload);
            $tokenPayload = is_array($fallbackPayload) ? $fallbackPayload : [];
        }

        $accessToken = trim((string) Arr::get($tokenPayload, 'access_token'));

        if ($accessToken === '') {
            throw ValidationException::withMessages([
                'login_method' => 'QQ 登录暂时不可用，请稍后重试。',
            ]);
        }

        $mePayload = $this->request()
            ->get('https://graph.qq.com/oauth2.0/me', [
                'access_token' => $accessToken,
                'fmt' => 'json',
            ])
            ->throw()
            ->json();

        if (! is_array($mePayload) || filled(Arr::get($mePayload, 'error'))) {
            throw ValidationException::withMessages([
                'login_method' => 'QQ 登录未返回有效身份，请稍后重试。',
            ]);
        }

        $openid = trim((string) Arr::get($mePayload, 'openid'));

        if ($openid === '') {
            throw ValidationException::withMessages([
                'login_method' => 'QQ 登录未返回有效身份，请稍后重试。',
            ]);
        }

        $profilePayload = [];

        try {
            $profilePayload = $this->request()
                ->get('https://graph.qq.com/user/get_user_info', [
                    'access_token' => $accessToken,
                    'oauth_consumer_key' => $this->clientId(),
                    'openid' => $openid,
                ])
                ->throw()
                ->json();
        } catch (\Throwable) {
            $profilePayload = [];
        }

        $name = $this->normalizeOptionalString(Arr::get($profilePayload, 'nickname'))
            ?? 'QQ用户'.Str::upper(substr(md5($openid), 0, 6));

        $avatarUrl = $this->normalizeOptionalString(Arr::get($profilePayload, 'figureurl_qq_2'))
            ?? $this->normalizeOptionalString(Arr::get($profilePayload, 'figureurl_2'))
            ?? $this->normalizeOptionalString(Arr::get($profilePayload, 'figureurl_qq_1'))
            ?? $this->normalizeOptionalString(Arr::get($profilePayload, 'figureurl_1'));

        return new SocialAuthIdentity(
            provider: 'qq',
            providerUserId: $openid,
            name: $name,
            avatarUrl: $avatarUrl,
            profileSnapshot: [
                'openid' => $openid,
                'name' => $name,
                'avatar_url' => $avatarUrl,
                'raw' => $this->sanitizeRawProfile($profilePayload),
            ],
        );
    }

    private function request(): PendingRequest
    {
        return $this->http->acceptJson()->timeout((int) config('services.qq.timeout', 8));
    }

    private function clientId(): string
    {
        return (string) config('services.qq.client_id');
    }

    private function clientSecret(): string
    {
        return (string) config('services.qq.client_secret');
    }

    private function redirectUri(): string
    {
        return (string) config('services.qq.redirect');
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
            'ret',
            'msg',
            'nickname',
            'gender',
            'province',
            'city',
            'year',
            'figureurl',
            'figureurl_1',
            'figureurl_2',
            'figureurl_qq_1',
            'figureurl_qq_2',
        ]);
    }
}
