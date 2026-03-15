<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Support\AdminActivityNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginUserResolver
{
    public function __construct(
        private readonly AdminActivityNotifier $adminActivityNotifier,
    ) {}

    /**
     * @param  array<string, mixed>  $identity
     */
    public function resolve(array $identity): User
    {
        $email = $this->normalizeOptionalEmail(Arr::get($identity, 'email'));
        $phone = $this->normalizeOptionalPhone(Arr::get($identity, 'phone'));

        if ($email === null && $phone === null) {
            throw ValidationException::withMessages([
                'target' => '登录服务未返回可用的账号标识，请稍后重试。',
            ]);
        }

        $name = $this->resolveDisplayName($identity, $email, $phone);
        $avatarUrl = $this->normalizeOptionalString(Arr::get($identity, 'image'));
        $emailVerified = (bool) Arr::get($identity, 'emailVerified', false);
        $phoneVerified = (bool) Arr::get($identity, 'phoneVerified', false);

        $user = User::query()
            ->when($email !== null, fn (Builder $query) => $query->where('email', $email))
            ->when($phone !== null, fn (Builder $query) => $query->orWhere('phone', $phone))
            ->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => User::ROLE_MEMBER,
                'avatar_url' => $avatarUrl,
                'email_verified_at' => $emailVerified ? now() : null,
                'phone_verified_at' => $phoneVerified ? now() : null,
                'last_seen_at' => now(),
            ]);

            $this->adminActivityNotifier->sendUserRegistered(
                $user,
                $phone !== null ? '手机验证码登录' : '邮箱验证码登录',
            );

            return $user;
        }

        $user->forceFill([
            'name' => $user->name ?: $name,
            'email' => $user->email ?: $email,
            'phone' => $user->phone ?: $phone,
            'avatar_url' => $user->avatar_url ?: $avatarUrl,
            'email_verified_at' => $emailVerified ? ($user->email_verified_at ?? now()) : $user->email_verified_at,
            'phone_verified_at' => $phoneVerified ? ($user->phone_verified_at ?? now()) : $user->phone_verified_at,
            'last_seen_at' => now(),
        ])->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $identity
     */
    private function resolveDisplayName(array $identity, ?string $email, ?string $phone): string
    {
        $name = $this->normalizeOptionalString(Arr::get($identity, 'name'));

        if ($name !== null) {
            return $name;
        }

        if ($email !== null) {
            return Str::before($email, '@');
        }

        return '用户'.substr((string) $phone, -4);
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
