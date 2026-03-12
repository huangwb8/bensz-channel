<?php

namespace App\Support;

use App\Enums\CdnMode;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SiteSettingsManager
{
    private const CACHE_KEY = 'site_settings.current';

    private const AVAILABLE_AUTH_METHODS = [
        'email_code',
        'email_password',
        'wechat_qr',
        'qq_qr',
    ];

    private const DEFAULT_AUTH_METHODS = [
        'email_code',
        'email_password',
        'wechat_qr',
        'qq_qr',
    ];

    public function current(): ?SiteSetting
    {
        if (! $this->tableExists()) {
            return null;
        }

        return Cache::rememberForever(self::CACHE_KEY, static fn (): ?SiteSetting => SiteSetting::query()->first());
    }

    public function siteFormData(): array
    {
        $setting = $this->current();

        return [
            'app_name' => $setting?->app_name ?? (string) config('app.name'),
            'site_name' => $setting?->site_name ?? (string) config('community.site.name'),
            'site_tagline' => $setting?->site_tagline ?? (string) config('community.site.tagline'),
            'auth_enabled_methods' => $this->normalizeAuthMethods($setting?->auth_enabled_methods),
            'theme_mode' => $setting?->theme_mode ?? (string) config('community.theme.mode', 'auto'),
            'theme_day_start' => $setting?->theme_day_start ?? (string) config('community.theme.day_start', '07:00'),
            'theme_night_start' => $setting?->theme_night_start ?? (string) config('community.theme.night_start', '19:00'),
            'article_image_max_mb' => $setting?->article_image_max_mb
                ?? (int) config('community.uploads.article_image_max_mb', 50),
            'article_video_max_mb' => $setting?->article_video_max_mb
                ?? (int) config('community.uploads.video_max_mb', 500),
        ];
    }

    public function cdnFormData(): array
    {
        $setting = $this->current();

        return [
            'cdn_asset_url' => $setting?->cdn_asset_url ?? (string) config('app.asset_url'),
            'cdn_mode' => $setting?->cdn_mode?->value ?? (string) config('cdn.mode', CdnMode::ORIGIN->value),
            'cdn_storage_provider' => $setting?->cdn_storage_provider ?? (string) config('cdn.storage.provider', 'dogecloud'),
            'cdn_storage_access_key' => '',
            'cdn_storage_secret_key' => '',
            'cdn_storage_bucket' => $setting?->cdn_storage_bucket ?? (string) config('cdn.storage.bucket'),
            'cdn_storage_region' => $setting?->cdn_storage_region ?? (string) config('cdn.storage.region', 'auto'),
            'cdn_storage_endpoint' => $setting?->cdn_storage_endpoint ?? (string) config('cdn.storage.endpoint'),
            'cdn_sync_enabled' => $setting?->cdn_sync_enabled ?? (bool) config('cdn.sync.enabled', false),
            'cdn_sync_on_build' => $setting?->cdn_sync_on_build ?? (bool) config('cdn.sync.on_build', true),
            'cdn_storage_access_key_masked' => $this->maskSecret($setting?->cdn_storage_access_key),
            'cdn_storage_secret_key_masked' => $this->maskSecret($setting?->cdn_storage_secret_key),
        ];
    }

    public function siteUsingOverrides(): bool
    {
        $setting = $this->current();

        return $setting instanceof SiteSetting
            && (
                filled($setting->app_name)
                || filled($setting->site_name)
                || filled($setting->site_tagline)
                || $setting->auth_enabled_methods !== null
                || filled($setting->theme_mode)
                || filled($setting->theme_day_start)
                || filled($setting->theme_night_start)
                || $setting->article_image_max_mb !== null
                || $setting->article_video_max_mb !== null
            );
    }

    public function cdnUsingOverrides(): bool
    {
        $setting = $this->current();

        return $setting instanceof SiteSetting
            && (
                filled($setting->cdn_asset_url)
                || $setting->cdn_mode !== null
                || filled($setting->cdn_storage_provider)
                || filled($setting->cdn_storage_access_key)
                || filled($setting->cdn_storage_secret_key)
                || filled($setting->cdn_storage_bucket)
                || filled($setting->cdn_storage_region)
                || filled($setting->cdn_storage_endpoint)
                || $setting->cdn_sync_enabled !== null
                || $setting->cdn_sync_on_build !== null
            );
    }

    public function availableAuthMethods(): array
    {
        return self::AVAILABLE_AUTH_METHODS;
    }

    public function enabledAuthMethods(): array
    {
        return $this->normalizeAuthMethods($this->current()?->auth_enabled_methods);
    }

    public function enabledQrProviders(): array
    {
        return $this->enabledQrProvidersFromMethods($this->enabledAuthMethods());
    }

    public function save(array $validated): SiteSetting
    {
        $setting = SiteSetting::query()->firstOrNew(['id' => 1]);
        $setting->fill([
            'app_name' => $this->resolvedValue($validated, 'app_name', fn () => $this->nullableString($validated['app_name'] ?? null), $setting->app_name),
            'site_name' => $this->resolvedValue($validated, 'site_name', fn () => $this->nullableString($validated['site_name'] ?? null), $setting->site_name),
            'site_tagline' => $this->resolvedValue($validated, 'site_tagline', fn () => $this->nullableString($validated['site_tagline'] ?? null), $setting->site_tagline),
            'auth_enabled_methods' => $this->resolvedValue($validated, 'auth_enabled_methods', fn () => $this->normalizeAuthMethods($validated['auth_enabled_methods'] ?? null), $setting->auth_enabled_methods),
            'cdn_asset_url' => $this->resolvedValue($validated, 'cdn_asset_url', fn () => $this->normalizeUrl($validated['cdn_asset_url'] ?? null), $setting->cdn_asset_url),
            'cdn_mode' => $this->resolvedValue($validated, 'cdn_mode', fn () => $this->normalizeCdnMode($validated['cdn_mode'] ?? null), $setting->cdn_mode?->value ?? CdnMode::ORIGIN->value),
            'cdn_storage_provider' => $this->resolvedValue($validated, 'cdn_storage_provider', fn () => $this->nullableString($validated['cdn_storage_provider'] ?? null), $setting->cdn_storage_provider),
            'cdn_storage_access_key' => $this->resolvedSecretValue($validated, 'cdn_storage_access_key', $setting->cdn_storage_access_key),
            'cdn_storage_secret_key' => $this->resolvedSecretValue($validated, 'cdn_storage_secret_key', $setting->cdn_storage_secret_key),
            'cdn_storage_bucket' => $this->resolvedValue($validated, 'cdn_storage_bucket', fn () => $this->nullableString($validated['cdn_storage_bucket'] ?? null), $setting->cdn_storage_bucket),
            'cdn_storage_region' => $this->resolvedValue($validated, 'cdn_storage_region', fn () => $this->nullableString($validated['cdn_storage_region'] ?? null), $setting->cdn_storage_region),
            'cdn_storage_endpoint' => $this->resolvedValue($validated, 'cdn_storage_endpoint', fn () => $this->normalizeUrl($validated['cdn_storage_endpoint'] ?? null), $setting->cdn_storage_endpoint),
            'cdn_sync_enabled' => $this->resolvedValue($validated, 'cdn_sync_enabled', fn () => $this->normalizeBoolean($validated['cdn_sync_enabled'] ?? null, false), $setting->cdn_sync_enabled ?? false),
            'cdn_sync_on_build' => $this->resolvedValue($validated, 'cdn_sync_on_build', fn () => $this->normalizeBoolean($validated['cdn_sync_on_build'] ?? null, true), $setting->cdn_sync_on_build ?? true),
            'theme_mode' => $this->resolvedValue($validated, 'theme_mode', fn () => $this->normalizeThemeMode($validated['theme_mode'] ?? null), $setting->theme_mode),
            'theme_day_start' => $this->resolvedValue($validated, 'theme_day_start', fn () => $this->normalizeThemeTime($validated['theme_day_start'] ?? null, '07:00'), $setting->theme_day_start),
            'theme_night_start' => $this->resolvedValue($validated, 'theme_night_start', fn () => $this->normalizeThemeTime($validated['theme_night_start'] ?? null, '19:00'), $setting->theme_night_start),
            'article_image_max_mb' => $this->resolvedValue(
                $validated,
                'article_image_max_mb',
                fn () => $this->normalizeImageMaxMb($validated['article_image_max_mb'] ?? null),
                $setting->article_image_max_mb ?? $this->normalizeImageMaxMb(config('community.uploads.article_image_max_mb', 50)),
            ),
            'article_video_max_mb' => $this->resolvedValue(
                $validated,
                'article_video_max_mb',
                fn () => $this->normalizeVideoMaxMb($validated['article_video_max_mb'] ?? null),
                $setting->article_video_max_mb ?? $this->normalizeVideoMaxMb(config('community.uploads.video_max_mb', 500)),
            ),
        ]);
        $setting->save();

        $this->forgetCached();

        return $setting->fresh();
    }

    public function applyConfiguredSettings(): void
    {
        $setting = $this->current();

        if ($setting instanceof SiteSetting) {
            if (filled($setting->app_name)) {
                config(['app.name' => $setting->app_name]);
            }

            if (filled($setting->site_name)) {
                config(['community.site.name' => $setting->site_name]);
            }

            if (filled($setting->site_tagline)) {
                config(['community.site.tagline' => $setting->site_tagline]);
            }

            config(['community.auth.enabled_methods' => $this->normalizeAuthMethods($setting->auth_enabled_methods)]);

            $cdnMode = $this->normalizeCdnMode($setting->cdn_mode?->value ?? null);
            $cdnAssetUrl = filled($setting->cdn_asset_url)
                ? rtrim((string) $setting->cdn_asset_url, '/')
                : null;

            config([
                'app.asset_url' => $cdnAssetUrl,
                'cdn.mode' => $cdnMode,
                'cdn.origin.asset_url' => $cdnMode === CdnMode::ORIGIN->value ? $cdnAssetUrl : config('cdn.origin.asset_url'),
                'cdn.storage.provider' => $setting->cdn_storage_provider ?: config('cdn.storage.provider'),
                'cdn.storage.access_key' => $setting->cdn_storage_access_key ?: config('cdn.storage.access_key'),
                'cdn.storage.secret_key' => $setting->cdn_storage_secret_key ?: config('cdn.storage.secret_key'),
                'cdn.storage.bucket' => $setting->cdn_storage_bucket ?: config('cdn.storage.bucket'),
                'cdn.storage.region' => $setting->cdn_storage_region ?: config('cdn.storage.region'),
                'cdn.storage.endpoint' => $setting->cdn_storage_endpoint ?: config('cdn.storage.endpoint'),
                'cdn.storage.public_url' => $cdnMode === CdnMode::STORAGE->value ? $cdnAssetUrl : config('cdn.storage.public_url'),
                'cdn.sync.enabled' => (bool) ($setting->cdn_sync_enabled ?? config('cdn.sync.enabled', false)),
                'cdn.sync.on_build' => (bool) ($setting->cdn_sync_on_build ?? config('cdn.sync.on_build', true)),
            ]);

            config([
                'community.theme.mode' => $this->normalizeThemeMode($setting->theme_mode ?? null),
                'community.theme.day_start' => $this->normalizeThemeTime($setting->theme_day_start ?? null, '07:00'),
                'community.theme.night_start' => $this->normalizeThemeTime($setting->theme_night_start ?? null, '19:00'),
                'community.uploads.article_image_max_mb' => $this->normalizeImageMaxMb($setting->article_image_max_mb),
                'community.uploads.video_max_mb' => $this->normalizeVideoMaxMb($setting->article_video_max_mb),
            ]);
        }

        config(['community.auth.qr_providers' => $this->enabledQrProvidersFromMethods((array) config('community.auth.enabled_methods', self::DEFAULT_AUTH_METHODS))]);
    }

    public function forgetCached(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('site_settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function resolvedValue(array $validated, string $key, callable $resolver, mixed $fallback): mixed
    {
        return array_key_exists($key, $validated) ? $resolver() : $fallback;
    }

    private function resolvedSecretValue(array $validated, string $key, ?string $fallback): ?string
    {
        if (! array_key_exists($key, $validated)) {
            return $fallback;
        }

        $resolved = $this->nullableString($validated[$key] ?? null);

        return $resolved ?? $fallback;
    }

    private function normalizeBoolean(mixed $value, bool $fallback): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value) || is_int($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $fallback;
        }

        return $fallback;
    }

    private function normalizeCdnMode(mixed $value): string
    {
        if ($value instanceof CdnMode) {
            return $value->value;
        }

        if (! is_string($value)) {
            return CdnMode::ORIGIN->value;
        }

        return CdnMode::tryFrom(trim($value))?->value ?? CdnMode::ORIGIN->value;
    }

    private function maskSecret(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $length = mb_strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return mb_substr($value, 0, 2).str_repeat('*', max(4, $length - 4)).mb_substr($value, -2);
    }


    private function normalizeUrl(mixed $value): ?string
    {
        $normalized = $this->nullableString($value);

        if ($normalized === null) {
            return null;
        }

        return rtrim($normalized, '/');
    }

    private function normalizeAuthMethods(mixed $value): array
    {
        $methods = is_array($value) ? $value : self::DEFAULT_AUTH_METHODS;

        $methods = array_values(array_unique(array_filter(
            array_map(static fn (mixed $item): string => is_string($item) ? trim($item) : '', $methods),
            static fn (string $item): bool => in_array($item, self::AVAILABLE_AUTH_METHODS, true),
        )));

        return $methods !== [] ? $methods : self::DEFAULT_AUTH_METHODS;
    }

    private function normalizeThemeMode(mixed $value): string
    {
        if (! is_string($value)) {
            return 'auto';
        }

        $mode = strtolower(trim($value));

        return in_array($mode, ['light', 'dark', 'auto'], true) ? $mode : 'auto';
    }

    private function normalizeThemeTime(mixed $value, string $fallback): string
    {
        if (! is_string($value)) {
            return $fallback;
        }

        $time = trim($value);

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
            return $fallback;
        }

        return $time;
    }

    private function normalizeImageMaxMb(mixed $value, int $fallback = 50): int
    {
        $normalized = (int) $value;

        if ($normalized <= 0) {
            return $fallback;
        }

        return max(1, min(100, $normalized));
    }

    private function normalizeVideoMaxMb(mixed $value, int $fallback = 500): int
    {
        $normalized = (int) $value;

        if ($normalized <= 0) {
            return $fallback;
        }

        return max(1, min(10240, $normalized));
    }

    private function enabledQrProvidersFromMethods(array $methods): array
    {
        $providers = [];

        if (in_array('wechat_qr', $methods, true)) {
            $providers[] = 'wechat';
        }

        if (in_array('qq_qr', $methods, true)) {
            $providers[] = 'qq';
        }

        return $providers;
    }
}
