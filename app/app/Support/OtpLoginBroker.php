<?php

namespace App\Support;

use App\Models\LoginCode;
use App\Models\User;
use App\Notifications\LoginCodeNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpLoginBroker
{
    public function issue(string $channel, string $target): string
    {
        $normalizedTarget = $this->normalizeTarget($channel, $target);
        $code = $this->generateCode();

        LoginCode::query()
            ->where('channel', $channel)
            ->where('target', $normalizedTarget)
            ->delete();

        LoginCode::query()->create([
            'channel' => $channel,
            'target' => $normalizedTarget,
            'code' => $code,
            'expires_at' => now()->addMinutes(config('community.auth.otp_ttl_minutes')),
        ]);

        if ($channel === LoginCode::CHANNEL_EMAIL) {
            Notification::route('mail', $normalizedTarget)->notify(
                new LoginCodeNotification($code, config('community.auth.otp_ttl_minutes')),
            );
        }

        if ($channel === LoginCode::CHANNEL_PHONE) {
            Log::info('Phone login code issued', [
                'phone' => $normalizedTarget,
                'code' => $code,
            ]);
        }

        return $code;
    }

    public function consume(string $channel, string $target, string $code, ?string $name = null): User
    {
        $normalizedTarget = $this->normalizeTarget($channel, $target);

        $loginCode = LoginCode::query()
            ->active()
            ->where('channel', $channel)
            ->where('target', $normalizedTarget)
            ->latest('id')
            ->first();

        if ($loginCode === null || $loginCode->code !== $code) {
            throw ValidationException::withMessages([
                'code' => '验证码不正确或已过期。',
            ]);
        }

        $loginCode->update(['consumed_at' => now()]);

        $user = $this->resolveUser($channel, $normalizedTarget, $name);

        $user->forceFill(['last_seen_at' => now()])->save();

        return $user;
    }

    private function resolveUser(string $channel, string $target, ?string $name): User
    {
        if ($channel === LoginCode::CHANNEL_EMAIL) {
            $user = User::query()->firstOrCreate([
                'email' => $target,
            ], [
                'name' => $name ?: Str::before($target, '@'),
                'role' => User::ROLE_MEMBER,
                'email_verified_at' => now(),
            ]);

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return $user;
        }

        $user = User::query()->firstOrCreate([
            'phone' => $target,
        ], [
            'name' => $name ?: '用户'.substr($target, -4),
            'role' => User::ROLE_MEMBER,
            'phone_verified_at' => now(),
        ]);

        if ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return $user;
    }

    private function normalizeTarget(string $channel, string $target): string
    {
        return $channel === LoginCode::CHANNEL_EMAIL
            ? Str::lower(trim($target))
            : (preg_replace('/\D+/', '', $target) ?: '');
    }

    private function generateCode(): string
    {
        $length = config('community.auth.otp_length');

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
