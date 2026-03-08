<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserAccountManager
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeProfileInput(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => $this->normalizeOptionalEmail($input['email'] ?? null),
            'phone' => $this->normalizeOptionalPhone($input['phone'] ?? null),
            'avatar_url' => $this->normalizeOptionalUrl($input['avatar_url'] ?? null),
            'bio' => $this->normalizeOptionalString($input['bio'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileValidationRules(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function assertHasLoginIdentifier(array $attributes): void
    {
        if (blank($attributes['email'] ?? null) && blank($attributes['phone'] ?? null)) {
            throw ValidationException::withMessages([
                'email' => '邮箱和手机号至少保留一个，避免用户失去登录标识。',
                'phone' => '邮箱和手机号至少保留一个，避免用户失去登录标识。',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function fillProfile(User $user, array $attributes): void
    {
        $email = $attributes['email'] ?? null;
        $phone = $attributes['phone'] ?? null;

        $emailChanged = $email !== $user->email;
        $phoneChanged = $phone !== $user->phone;

        $payload = [
            'name' => $attributes['name'] ?? $user->name,
        ];

        if (array_key_exists('email', $attributes)) {
            $payload['email'] = $email;
        }

        if (array_key_exists('phone', $attributes)) {
            $payload['phone'] = $phone;
        }

        if (array_key_exists('avatar_url', $attributes)) {
            $payload['avatar_url'] = $attributes['avatar_url'];
        }

        if (array_key_exists('bio', $attributes)) {
            $payload['bio'] = $attributes['bio'];
        }

        $user->fill($payload);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if ($phoneChanged) {
            $user->phone_verified_at = null;
        }
    }

    private function normalizeOptionalEmail(mixed $value): ?string
    {
        $email = $this->normalizeOptionalString($value);

        return $email === null ? null : Str::lower($email);
    }

    private function normalizeOptionalPhone(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $value) ?: '';

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

    private function normalizeOptionalUrl(mixed $value): ?string
    {
        $url = $this->normalizeOptionalString($value);

        return $url === null ? null : rtrim($url, '/');
    }
}
