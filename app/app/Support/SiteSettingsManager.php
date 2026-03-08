<?php

namespace App\Support;

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

    public function formData(): array
    {
        $setting = $this->current();

        return [
            'app_name' => $setting?->app_name ?? (string) config('app.name'),
            'site_name' => $setting?->site_name ?? (string) config('community.site.name'),
            'site_tagline' => $setting?->site_tagline ?? (string) config('community.site.tagline'),
            'auth_enabled_methods' => $this->normalizeAuthMethods($setting?->auth_enabled_methods),
        ];
    }

    public function usingOverrides(): bool
    {
        $setting = $this->current();

        return $setting instanceof SiteSetting
            && (
                filled($setting->app_name)
                || filled($setting->site_name)
                || filled($setting->site_tagline)
                || $setting->auth_enabled_methods !== null
            );
    }

    public function availableAuthMethods(): array
    {
        return self::AVAILABLE_AUTH_METHODS;
    }

    public function save(array $validated): SiteSetting
    {
        $setting = SiteSetting::query()->firstOrNew(['id' => 1]);
        $setting->fill([
            'app_name' => $this->nullableString($validated['app_name'] ?? null),
            'site_name' => $this->nullableString($validated['site_name'] ?? null),
            'site_tagline' => $this->nullableString($validated['site_tagline'] ?? null),
            'auth_enabled_methods' => $this->normalizeAuthMethods($validated['auth_enabled_methods'] ?? null),
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
        }

        config(['community.auth.qr_providers' => $this->enabledQrProviders((array) config('community.auth.enabled_methods', self::DEFAULT_AUTH_METHODS))]);
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

    private function normalizeAuthMethods(mixed $value): array
    {
        $methods = is_array($value) ? $value : self::DEFAULT_AUTH_METHODS;

        $methods = array_values(array_unique(array_filter(
            array_map(static fn (mixed $item): string => is_string($item) ? trim($item) : '', $methods),
            static fn (string $item): bool => in_array($item, self::AVAILABLE_AUTH_METHODS, true),
        )));

        return $methods !== [] ? $methods : self::DEFAULT_AUTH_METHODS;
    }

    private function enabledQrProviders(array $methods): array
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
