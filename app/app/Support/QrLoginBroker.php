<?php

namespace App\Support;

use App\Models\QrLoginRequest;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QrLoginBroker
{
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

        $socialAccount = SocialAccount::query()
            ->where('provider', $qrLoginRequest->provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        $user = $socialAccount?->user;

        if ($user === null && $email !== null) {
            $user = User::query()->where('email', $email)->first();
        }

        if ($user === null && $phone !== null) {
            $user = User::query()->where('phone', $phone)->first();
        }

        if ($user === null) {
            $user = User::query()->create([
                'name' => $payload['name'],
                'email' => $email,
                'phone' => $phone,
                'email_verified_at' => $email ? now() : null,
                'phone_verified_at' => $phone ? now() : null,
                'role' => User::ROLE_MEMBER,
                'last_seen_at' => now(),
            ]);
        }

        $user->fill([
            'name' => $user->name ?: $payload['name'],
            'email' => $user->email ?: $email,
            'phone' => $user->phone ?: $phone,
            'last_seen_at' => now(),
        ])->save();

        SocialAccount::query()->updateOrCreate([
            'provider' => $qrLoginRequest->provider,
            'provider_user_id' => $providerUserId,
        ], [
            'user_id' => $user->id,
            'profile_snapshot' => [
                'name' => $payload['name'],
                'email' => $email,
                'phone' => $phone,
            ],
        ]);

        $qrLoginRequest->update([
            'status' => QrLoginRequest::STATUS_APPROVED,
            'approved_user_id' => $user->id,
        ]);

        return $user;
    }
}
