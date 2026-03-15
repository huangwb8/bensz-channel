<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserAccountManager
{
    public function __construct(
        private readonly AvatarPresenter $avatarPresenter,
    ) {}

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
            'avatar_type' => $this->normalizeAvatarType($input['avatar_type'] ?? null, $input['avatar_url'] ?? null),
            'avatar_style' => $this->avatarPresenter->resolveStyle((string) ($input['avatar_style'] ?? '')),
            'avatar_url' => $this->normalizeOptionalUrl($input['avatar_url'] ?? null),
            'bio' => $this->normalizeOptionalString($input['bio'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizePartialProfileInput(array $input): array
    {
        $normalized = [];

        if (array_key_exists('name', $input)) {
            $normalized['name'] = trim((string) $input['name']);
        }

        if (array_key_exists('email', $input)) {
            $normalized['email'] = $this->normalizeOptionalEmail($input['email']);
        }

        if (array_key_exists('phone', $input)) {
            $normalized['phone'] = $this->normalizeOptionalPhone($input['phone']);
        }

        if (array_key_exists('avatar_type', $input) || array_key_exists('avatar_url', $input)) {
            $normalized['avatar_type'] = $this->normalizeAvatarType($input['avatar_type'] ?? null, $input['avatar_url'] ?? null);
        }

        if (array_key_exists('avatar_style', $input)) {
            $normalized['avatar_style'] = $this->avatarPresenter->resolveStyle((string) $input['avatar_style']);
        }

        if (array_key_exists('avatar_url', $input)) {
            $normalized['avatar_url'] = $this->normalizeOptionalUrl($input['avatar_url']);
        }

        if (array_key_exists('bio', $input)) {
            $normalized['bio'] = $this->normalizeOptionalString($input['bio']);
        }

        return $normalized;
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
            'avatar_type' => ['nullable', Rule::in(['generated', 'external', 'uploaded'])],
            'avatar_style' => ['nullable', Rule::in(array_column($this->avatarPresenter->styles(), 'id'))],
            'avatar_url' => ['nullable', 'url', 'max:2048', Rule::requiredIf(fn () => request()->input('avatar_type') === 'external')],
            'avatar_upload' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:1024', Rule::requiredIf(fn () => request()->input('avatar_type') === 'uploaded' && ! $this->requestKeepsExistingUploadedAvatar($user))],
            'bio' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createUserValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')],
            'avatar_type' => ['nullable', Rule::in(['generated', 'external', 'uploaded'])],
            'avatar_style' => ['nullable', Rule::in(array_column($this->avatarPresenter->styles(), 'id'))],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
            'avatar_upload' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:1024'],
            'bio' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function partialProfileValidationRules(User $user): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:40'],
            'email' => ['sometimes', 'nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'avatar_type' => ['sometimes', 'nullable', Rule::in(['generated', 'external', 'uploaded'])],
            'avatar_style' => ['sometimes', 'nullable', Rule::in(array_column($this->avatarPresenter->styles(), 'id'))],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'avatar_upload' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:1024'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
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

        if (
            array_key_exists('avatar_url', $attributes)
            || array_key_exists('avatar_type', $attributes)
            || array_key_exists('avatar_style', $attributes)
            || array_key_exists('avatar_upload', $attributes)
        ) {
            $avatarPayload = $this->resolveAvatarPayload($user, $attributes);
            $payload = array_merge($payload, $avatarPayload);
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

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{avatar_type: string, avatar_style: string, avatar_url: ?string}
     */
    private function resolveAvatarPayload(User $user, array $attributes): array
    {
        $nextType = $attributes['avatar_type']
            ?? (array_key_exists('avatar_url', $attributes)
                ? $this->normalizeAvatarType(null, $attributes['avatar_url'])
                : ($user->avatar_type ?: 'generated'));
        $nextStyle = $attributes['avatar_style'] ?? ($user->avatar_style ?: $this->avatarPresenter->defaultStyle());
        $nextUrl = $attributes['avatar_url'] ?? $user->avatar_url;
        $upload = $attributes['avatar_upload'] ?? null;

        if ($nextType === 'uploaded') {
            $nextUrl = $this->storeAvatarUpload($user, $upload, $user->avatar_type === 'uploaded' ? $user->avatar_url : null);
        } elseif ($nextType === 'generated') {
            $this->deleteUploadedAvatar($user->avatar_type === 'uploaded' ? $user->avatar_url : null);
            $nextUrl = null;
        } elseif ($nextType === 'external') {
            $this->deleteUploadedAvatar($user->avatar_type === 'uploaded' ? $user->avatar_url : null);
            $nextUrl = $this->normalizeOptionalUrl($nextUrl);
        }

        return [
            'avatar_type' => $nextType,
            'avatar_style' => $this->avatarPresenter->resolveStyle($nextStyle),
            'avatar_url' => $nextUrl,
        ];
    }

    private function normalizeAvatarType(mixed $value, mixed $avatarUrl): string
    {
        $type = $this->normalizeOptionalString($value);

        if (in_array($type, ['generated', 'external', 'uploaded'], true)) {
            return $type;
        }

        return $this->normalizeOptionalUrl($avatarUrl) === null
            ? 'generated'
            : 'external';
    }

    private function requestKeepsExistingUploadedAvatar(User $user): bool
    {
        return request()->input('avatar_type') === 'uploaded'
            && $user->avatar_type === 'uploaded'
            && filled($user->avatar_url);
    }

    private function storeAvatarUpload(User $user, mixed $upload, ?string $previousUrl): ?string
    {
        if (! $upload instanceof UploadedFile) {
            return $previousUrl;
        }

        $this->deleteUploadedAvatar($previousUrl);

        $directory = sprintf('avatars/%s/%s', now()->format('Y'), now()->format('m'));
        $extension = Str::lower($upload->getClientOriginalExtension() ?: $upload->extension() ?: 'png');
        $filename = sprintf('user-%s-%s.%s', $user->user_id ?: $user->id ?: 'new', Str::random(12), $extension);
        $path = $upload->storeAs($directory, $filename, 'public');

        if ($path === false) {
            return $previousUrl;
        }

        return '/storage/'.ltrim($path, '/');
    }

    private function deleteUploadedAvatar(?string $avatarUrl): void
    {
        $path = $this->publicStoragePathFromUrl($avatarUrl);

        if ($path === null) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function publicStoragePathFromUrl(?string $avatarUrl): ?string
    {
        if (! is_string($avatarUrl) || ! str_starts_with($avatarUrl, '/storage/')) {
            return null;
        }

        return ltrim(Str::after($avatarUrl, '/storage/'), '/');
    }
}
