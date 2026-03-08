<?php

namespace App\Services\Auth;

use App\Contracts\Auth\OtpAuthGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BetterAuthGateway implements OtpAuthGateway
{
    public function issue(string $channel, string $target): ?string
    {
        $response = $this->sendRequest('send', [
            'channel' => $channel,
            'target' => $this->normalizeTarget($channel, $target),
        ]);

        return $response->json('previewCode');
    }

    public function consume(string $channel, string $target, string $code, ?string $name = null): array
    {
        $response = $this->sendRequest('verify', [
            'channel' => $channel,
            'target' => $this->normalizeTarget($channel, $target),
            'code' => trim($code),
            'name' => filled($name) ? trim((string) $name) : null,
        ]);

        $user = $response->json('user');

        if (! is_array($user)) {
            throw ValidationException::withMessages([
                'target' => '登录服务返回了无效的用户信息，请稍后重试。',
            ]);
        }

        return [
            'id' => Arr::get($user, 'id'),
            'email' => $this->normalizeOptionalEmail(Arr::get($user, 'email')),
            'phone' => $this->normalizeOptionalPhone(Arr::get($user, 'phone')),
            'name' => $this->normalizeOptionalString(Arr::get($user, 'name')),
            'image' => $this->normalizeOptionalString(Arr::get($user, 'image')),
            'emailVerified' => (bool) Arr::get($user, 'emailVerified', false),
            'phoneVerified' => (bool) Arr::get($user, 'phoneVerified', false),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendRequest(string $action, array $payload)
    {
        try {
            $response = $this->request()->post('/internal/otp/'.$action, $payload);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'target' => '登录服务暂时不可用，请稍后重试。',
            ]);
        }

        if ($response->unprocessableEntity()) {
            $errors = $response->json('errors');

            throw ValidationException::withMessages(is_array($errors) && $errors !== []
                ? $errors
                : ['code' => [$response->json('message', '验证码不正确或已过期。')]]);
        }

        try {
            return $response->throw();
        } catch (RequestException) {
            throw ValidationException::withMessages([
                'target' => '登录服务暂时不可用，请稍后重试。',
            ]);
        }
    }

    private function request(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.better_auth.base_url'), '/');
        $secret = (string) config('services.better_auth.internal_secret');

        if ($baseUrl === '' || $secret === '') {
            throw ValidationException::withMessages([
                'target' => '登录服务配置不完整，请联系管理员。',
            ]);
        }

        return Http::acceptJson()
            ->asJson()
            ->baseUrl($baseUrl)
            ->timeout((int) config('services.better_auth.timeout', 5))
            ->withHeaders([
                'X-Internal-Auth-Secret' => $secret,
            ]);
    }

    private function normalizeTarget(string $channel, string $target): string
    {
        return $channel === 'email'
            ? Str::lower(trim($target))
            : (preg_replace('/\D+/', '', $target) ?: '');
    }

    private function normalizeOptionalEmail(mixed $value): ?string
    {
        $email = $this->normalizeOptionalString($value);

        return $email === null ? null : Str::lower($email);
    }

    private function normalizeOptionalPhone(mixed $value): ?string
    {
        $phone = $this->normalizeOptionalString($value);

        if ($phone === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $phone);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
