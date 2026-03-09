<?php

namespace App\Support;

use App\Data\SocialAuthIdentity;
use App\Models\QrLoginRequest;
use App\Models\User;
use App\Services\Auth\SocialAccountResolver;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QrLoginBroker
{
    public function __construct(private readonly SocialAccountResolver $resolver) {}

    public function create(string $provider): QrLoginRequest
    {
        return QrLoginRequest::query()->create([
            'provider' => $provider,
            'token' => (string) Str::uuid(),
            'status' => QrLoginRequest::STATUS_PENDING,
            'expires_at' => now()->addMinutes(config('community.auth.qr_ttl_minutes')),
        ]);
    }

    public function approve(QrLoginRequest $qrLoginRequest, array $payload): User
    {
        if ($qrLoginRequest->isExpired()) {
            $qrLoginRequest->update(['status' => QrLoginRequest::STATUS_EXPIRED]);

            throw ValidationException::withMessages([
                'token' => '二维码已过期，请重新发起扫码。',
            ]);
        }

        if ($qrLoginRequest->status !== QrLoginRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'token' => '该二维码已被使用。',
            ]);
        }

        $providerUserId = trim($payload['provider_user_id'] ?? '') ?: Str::slug($payload['name']).'-'.Str::lower(Str::random(6));
        $email = filled($payload['email'] ?? null) ? Str::lower(trim($payload['email'])) : null;
        $phone = filled($payload['phone'] ?? null) ? preg_replace('/\D+/', '', (string) $payload['phone']) : null;

        $user = $this->resolver->resolve(new SocialAuthIdentity(
            provider: $qrLoginRequest->provider,
            providerUserId: $providerUserId,
            name: (string) $payload['name'],
            email: $email,
            phone: $phone,
            profileSnapshot: [
                'mode' => 'demo',
                'name' => $payload['name'],
                'email' => $email,
                'phone' => $phone,
            ],
        ));

        $qrLoginRequest->update([
            'status' => QrLoginRequest::STATUS_APPROVED,
            'approved_user_id' => $user->id,
        ]);

        return $user;
    }
}
