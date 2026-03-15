<?php

namespace App\Services\Auth;

use App\Data\SocialAuthIdentity;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\AdminActivityNotifier;

class SocialAccountResolver
{
    public function __construct(
        private readonly AdminActivityNotifier $adminActivityNotifier,
    ) {}

    public function resolve(SocialAuthIdentity $identity): User
    {
        $socialAccount = SocialAccount::query()
            ->with('user')
            ->where('provider', $identity->provider)
            ->where('provider_user_id', $identity->providerUserId)
            ->first();

        $user = $socialAccount?->user;

        if ($user === null && $identity->email !== null) {
            $user = User::query()->where('email', $identity->email)->first();
        }

        if ($user === null && $identity->phone !== null) {
            $user = User::query()->where('phone', $identity->phone)->first();
        }

        if ($user === null) {
            $user = User::query()->create([
                'name' => $identity->name,
                'email' => $identity->email,
                'phone' => $identity->phone,
                'avatar_url' => $identity->avatarUrl,
                'role' => User::ROLE_MEMBER,
                'last_seen_at' => now(),
            ]);

            $this->adminActivityNotifier->sendUserRegistered(
                $user,
                $this->providerLabel($identity->provider),
            );
        }

        $this->touchUser($user, $identity);

        SocialAccount::query()->updateOrCreate([
            'provider' => $identity->provider,
            'provider_user_id' => $identity->providerUserId,
        ], [
            'user_id' => $user->id,
            'profile_snapshot' => $identity->profileSnapshot,
        ]);

        return $user;
    }

    private function touchUser(User $user, SocialAuthIdentity $identity): void
    {
        $payload = [
            'last_seen_at' => now(),
        ];

        if (blank($user->name)) {
            $payload['name'] = $identity->name;
        }

        if (blank($user->email) && $identity->email !== null) {
            $payload['email'] = $identity->email;
        }

        if (blank($user->phone) && $identity->phone !== null) {
            $payload['phone'] = $identity->phone;
        }

        if (blank($user->avatar_url) && $identity->avatarUrl !== null) {
            $payload['avatar_url'] = $identity->avatarUrl;
        }

        $user->fill($payload)->save();
    }

    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            'wechat' => '微信扫码登录',
            'qq' => 'QQ 扫码登录',
            default => strtoupper($provider).' 登录',
        };
    }
}
