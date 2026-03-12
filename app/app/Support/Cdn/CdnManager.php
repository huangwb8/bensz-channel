<?php

namespace App\Support\Cdn;

use App\Enums\CdnMode;
use App\Models\SiteSetting;
use App\Support\SiteSettingsManager;

class CdnManager
{
    public function __construct(private readonly SiteSettingsManager $siteSettingsManager) {}

    public function getMode(): CdnMode
    {
        $mode = $this->runtimeConfiguration()['mode'] ?? CdnMode::ORIGIN->value;

        return CdnMode::tryFrom((string) $mode) ?? CdnMode::ORIGIN;
    }

    public function getAssetUrl(string $path): string
    {
        $base = trim((string) config('app.asset_url', ''));
        $normalizedPath = ltrim($path, '/');

        return $base !== ''
            ? rtrim($base, '/').'/'.$normalizedPath
            : asset($normalizedPath);
    }

    public function isConfigured(): bool
    {
        return $this->validateRuntimeConfiguration() === [];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<int, string>
     */
    public function validateConfiguration(array $overrides = []): array
    {
        return $this->validateResolvedConfiguration($this->draftConfiguration($overrides));
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeConfiguration(): array
    {
        return [
            'mode' => CdnMode::tryFrom((string) config('cdn.mode', CdnMode::ORIGIN->value))?->value ?? CdnMode::ORIGIN->value,
            'asset_url' => $this->normalizeUrl(config('app.asset_url')),
            'storage_provider' => $this->normalizeString(config('cdn.storage.provider')),
            'storage_access_key' => $this->normalizeString(config('cdn.storage.access_key')),
            'storage_secret_key' => $this->normalizeString(config('cdn.storage.secret_key')),
            'storage_bucket' => $this->normalizeString(config('cdn.storage.bucket')),
            'storage_region' => $this->normalizeString(config('cdn.storage.region')),
            'storage_endpoint' => $this->normalizeUrl(config('cdn.storage.endpoint')),
            'sync_enabled' => filter_var(config('cdn.sync.enabled', false), FILTER_VALIDATE_BOOL),
            'sync_on_build' => filter_var(config('cdn.sync.on_build', true), FILTER_VALIDATE_BOOL),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function draftConfiguration(array $overrides = []): array
    {
        $setting = $this->siteSettingsManager->current();

        return $this->configurationFromSetting($setting, $overrides);
    }

    /**
     * @return array<int, string>
     */
    public function validateRuntimeConfiguration(): array
    {
        return $this->validateResolvedConfiguration($this->runtimeConfiguration());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function configurationFromSetting(?SiteSetting $setting, array $overrides = []): array
    {
        $mode = $overrides['cdn_mode']
            ?? $setting?->cdn_mode?->value
            ?? config('cdn.mode', CdnMode::ORIGIN->value);

        $assetUrl = $overrides['cdn_asset_url']
            ?? $setting?->cdn_asset_url
            ?? (($mode === CdnMode::STORAGE->value ? config('cdn.storage.public_url') : config('cdn.origin.asset_url')) ?: config('app.asset_url'));

        return [
            'mode' => CdnMode::tryFrom((string) $mode)?->value ?? CdnMode::ORIGIN->value,
            'asset_url' => $this->normalizeUrl($assetUrl),
            'storage_provider' => $this->normalizeString($overrides['cdn_storage_provider'] ?? $setting?->cdn_storage_provider ?? config('cdn.storage.provider')),
            'storage_access_key' => $this->resolveSecretOverride($overrides, 'cdn_storage_access_key', $setting?->cdn_storage_access_key, config('cdn.storage.access_key')),
            'storage_secret_key' => $this->resolveSecretOverride($overrides, 'cdn_storage_secret_key', $setting?->cdn_storage_secret_key, config('cdn.storage.secret_key')),
            'storage_bucket' => $this->normalizeString($overrides['cdn_storage_bucket'] ?? $setting?->cdn_storage_bucket ?? config('cdn.storage.bucket')),
            'storage_region' => $this->normalizeString($overrides['cdn_storage_region'] ?? $setting?->cdn_storage_region ?? config('cdn.storage.region')),
            'storage_endpoint' => $this->normalizeUrl($overrides['cdn_storage_endpoint'] ?? $setting?->cdn_storage_endpoint ?? config('cdn.storage.endpoint')),
            'sync_enabled' => filter_var($overrides['cdn_sync_enabled'] ?? $setting?->cdn_sync_enabled ?? config('cdn.sync.enabled', false), FILTER_VALIDATE_BOOL),
            'sync_on_build' => filter_var($overrides['cdn_sync_on_build'] ?? $setting?->cdn_sync_on_build ?? config('cdn.sync.on_build', true), FILTER_VALIDATE_BOOL),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function storageConfiguration(): array
    {
        return $this->storageConfigurationFromResolved($this->runtimeConfiguration());
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array<string, mixed>
     */
    public function storageConfigurationFromResolved(array $configuration): array
    {
        if (($configuration['mode'] ?? null) !== CdnMode::STORAGE->value) {
            return [];
        }

        return [
            'provider' => $configuration['storage_provider'],
            'key' => $configuration['storage_access_key'],
            'secret' => $configuration['storage_secret_key'],
            'bucket' => $configuration['storage_bucket'],
            'region' => $configuration['storage_region'],
            'endpoint' => $configuration['storage_endpoint'],
            'url' => $configuration['asset_url'],
            'use_path_style_endpoint' => (bool) config('cdn.storage.use_path_style_endpoint', false),
        ];
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array<int, string>
     */
    private function validateResolvedConfiguration(array $configuration): array
    {
        $errors = [];

        if (($configuration['mode'] ?? CdnMode::ORIGIN->value) === CdnMode::ORIGIN->value) {
            return $errors;
        }

        foreach ([
            'asset_url' => '对象存储模式需要填写公开资源域名。',
            'storage_provider' => '对象存储模式需要选择服务商。',
            'storage_bucket' => '对象存储模式需要填写存储桶名称。',
            'storage_region' => '对象存储模式需要填写存储区域。',
            'storage_endpoint' => '对象存储模式需要填写存储服务端点。',
            'storage_access_key' => '对象存储模式需要填写 Access Key。',
            'storage_secret_key' => '对象存储模式需要填写 Secret Key。',
        ] as $key => $message) {
            if (trim((string) ($configuration[$key] ?? '')) === '') {
                $errors[] = $message;
            }
        }

        return $errors;
    }

    private function resolveSecretOverride(array $overrides, string $key, mixed $settingValue, mixed $defaultValue): ?string
    {
        if (! array_key_exists($key, $overrides)) {
            return $this->normalizeString($settingValue ?? $defaultValue);
        }

        $resolved = $this->normalizeString($overrides[$key]);

        return $resolved ?? $this->normalizeString($settingValue ?? $defaultValue);
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function normalizeUrl(mixed $value): ?string
    {
        $url = $this->normalizeString($value);

        return $url !== null ? rtrim($url, '/') : null;
    }
}
