<?php

namespace App\Support;

use App\Enums\CdnMode;
use App\Models\SiteSetting;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SiteSettingsManager
{
    private const CACHE_KEY = 'site_settings.current';

    private const DEFAULT_TIMEZONE = 'Asia/Shanghai';

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

    private const PREFERRED_TIMEZONES = [
        'Asia/Shanghai' => '北京时间',
        'Asia/Singapore' => '新加坡时间',
        'Asia/Tokyo' => '东京时间',
        'Europe/London' => '伦敦时间',
        'Europe/Paris' => '巴黎时间',
        'America/New_York' => '纽约时间',
        'America/Los_Angeles' => '洛杉矶时间',
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
            'timezone' => $this->normalizeTimezone($setting?->timezone),
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
        $runtime = $this->cdnRuntimeData();

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
            'cdn_is_active' => $runtime['is_active'],
            'cdn_runtime_mode' => $runtime['mode'],
            'cdn_runtime_asset_url' => $runtime['asset_url'],
            'cdn_runtime_provider' => $runtime['provider'],
            'cdn_runtime_sync_enabled' => $runtime['sync_enabled'],
            'cdn_runtime_sync_on_build' => $runtime['sync_on_build'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cdnRuntimeData(): array
    {
        $setting = $this->current();
        $snapshot = $this->decodeCdnAppliedSnapshot($setting?->cdn_applied_snapshot);

        return [
            'is_active' => (bool) ($setting?->cdn_is_active ?? false) && $snapshot !== [],
            'mode' => $snapshot['mode'] ?? null,
            'asset_url' => $snapshot['asset_url'] ?? null,
            'provider' => $snapshot['storage_provider'] ?? null,
            'sync_enabled' => (bool) ($snapshot['sync_enabled'] ?? false),
            'sync_on_build' => (bool) ($snapshot['sync_on_build'] ?? false),
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
                || filled($setting->timezone)
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

    /**
     * @return array{preferred: array<string, string>, all: array<string, string>}
     */
    public function timezoneOptionGroups(): array
    {
        static $groups;

        if ($groups !== null) {
            return $groups;
        }

        $now = new DateTimeImmutable('now');
        $identifiers = DateTimeZone::listIdentifiers();
        $preferred = [];

        foreach (self::PREFERRED_TIMEZONES as $identifier => $label) {
            if (in_array($identifier, $identifiers, true)) {
                $preferred[$identifier] = $this->timezoneLabel($identifier, $label, $now);
            }
        }

        $all = [];

        foreach ($identifiers as $identifier) {
            if (array_key_exists($identifier, $preferred)) {
                continue;
            }

            $all[$identifier] = $this->timezoneLabel($identifier, null, $now);
        }

        return $groups = [
            'preferred' => $preferred,
            'all' => $all,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function availableTimezoneIdentifiers(): array
    {
        $groups = $this->timezoneOptionGroups();

        return [
            ...array_keys($groups['preferred']),
            ...array_keys($groups['all']),
        ];
    }

    public function save(array $validated): SiteSetting
    {
        $setting = SiteSetting::query()->firstOrNew(['id' => 1]);
        $currentTimezone = $this->normalizeTimezone($setting->timezone);
        $nextTimezone = $this->resolvedValue($validated, 'timezone', fn () => $this->normalizeTimezone($validated['timezone'] ?? null), $currentTimezone);

        DB::transaction(function () use ($setting, $validated, $currentTimezone, $nextTimezone): void {
            $setting->fill([
                'app_name' => $this->resolvedValue($validated, 'app_name', fn () => $this->nullableString($validated['app_name'] ?? null), $setting->app_name),
                'site_name' => $this->resolvedValue($validated, 'site_name', fn () => $this->nullableString($validated['site_name'] ?? null), $setting->site_name),
                'site_tagline' => $this->resolvedValue($validated, 'site_tagline', fn () => $this->nullableString($validated['site_tagline'] ?? null), $setting->site_tagline),
                'timezone' => $nextTimezone,
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

            if ($currentTimezone !== $nextTimezone) {
                $this->shiftStoredTimestamps($currentTimezone, $nextTimezone);
            }
        });

        $this->forgetCached();

        return $setting->fresh();
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    public function activateCdn(array $configuration): SiteSetting
    {
        $setting = SiteSetting::query()->firstOrNew(['id' => 1]);
        $setting->fill([
            'cdn_is_active' => true,
            'cdn_applied_snapshot' => json_encode(
                $this->normalizeCdnSnapshot($configuration),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
        ]);
        $setting->save();

        $this->forgetCached();

        return $setting->fresh();
    }

    public function deactivateCdn(): SiteSetting
    {
        $setting = SiteSetting::query()->firstOrNew(['id' => 1]);
        $setting->fill([
            'cdn_is_active' => false,
        ]);
        $setting->save();

        $this->forgetCached();

        return $setting->fresh();
    }

    public function applyConfiguredSettings(): void
    {
        $setting = $this->current();
        $timezone = $this->normalizeTimezone($setting?->timezone);

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

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

            $cdnRuntime = $this->resolvedRuntimeCdnConfiguration($setting);

            config([
                'app.asset_url' => $cdnRuntime['asset_url'],
                'cdn.mode' => $cdnRuntime['mode'],
                'cdn.origin.asset_url' => $cdnRuntime['mode'] === CdnMode::ORIGIN->value ? $cdnRuntime['asset_url'] : $this->defaultCdnConfiguration()['origin_asset_url'],
                'cdn.storage.provider' => $cdnRuntime['storage_provider'],
                'cdn.storage.access_key' => $cdnRuntime['storage_access_key'],
                'cdn.storage.secret_key' => $cdnRuntime['storage_secret_key'],
                'cdn.storage.bucket' => $cdnRuntime['storage_bucket'],
                'cdn.storage.region' => $cdnRuntime['storage_region'],
                'cdn.storage.endpoint' => $cdnRuntime['storage_endpoint'],
                'cdn.storage.public_url' => $cdnRuntime['mode'] === CdnMode::STORAGE->value ? $cdnRuntime['asset_url'] : $this->defaultCdnConfiguration()['storage_public_url'],
                'cdn.sync.enabled' => $cdnRuntime['sync_enabled'],
                'cdn.sync.on_build' => $cdnRuntime['sync_on_build'],
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

    private function normalizeTimezone(mixed $value): string
    {
        $timezone = is_string($value) ? trim($value) : '';

        if ($timezone !== '' && in_array($timezone, $this->availableTimezoneIdentifiers(), true)) {
            return $timezone;
        }

        $configuredTimezone = config('app.timezone');

        if (is_string($configuredTimezone) && in_array($configuredTimezone, $this->availableTimezoneIdentifiers(), true)) {
            return $configuredTimezone;
        }

        return self::DEFAULT_TIMEZONE;
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

    /**
     * @return array<string, mixed>
     */
    private function normalizeCdnSnapshot(array $configuration): array
    {
        return [
            'mode' => $this->normalizeCdnMode($configuration['mode'] ?? null),
            'asset_url' => $this->normalizeUrl($configuration['asset_url'] ?? null),
            'storage_provider' => $this->nullableString($configuration['storage_provider'] ?? null),
            'storage_access_key' => $this->nullableString($configuration['storage_access_key'] ?? null),
            'storage_secret_key' => $this->nullableString($configuration['storage_secret_key'] ?? null),
            'storage_bucket' => $this->nullableString($configuration['storage_bucket'] ?? null),
            'storage_region' => $this->nullableString($configuration['storage_region'] ?? null),
            'storage_endpoint' => $this->normalizeUrl($configuration['storage_endpoint'] ?? null),
            'sync_enabled' => $this->normalizeBoolean($configuration['sync_enabled'] ?? null, false),
            'sync_on_build' => $this->normalizeBoolean($configuration['sync_on_build'] ?? null, true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvedRuntimeCdnConfiguration(?SiteSetting $setting): array
    {
        $defaults = $this->defaultCdnConfiguration();
        $snapshot = $this->decodeCdnAppliedSnapshot($setting?->cdn_applied_snapshot);

        if (! ($setting?->cdn_is_active ?? false) || $snapshot === []) {
            return $defaults;
        }

        return array_replace($defaults, $snapshot);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultCdnConfiguration(): array
    {
        $mode = $this->normalizeCdnMode(env('CDN_MODE', CdnMode::ORIGIN->value));
        $originAssetUrl = $this->normalizeUrl(env('CDN_ASSET_URL', env('ASSET_URL')));
        $storagePublicUrl = $this->normalizeUrl(env('CDN_STORAGE_PUBLIC_URL', env('CDN_ASSET_URL', env('ASSET_URL'))));

        return [
            'mode' => $mode,
            'asset_url' => $mode === CdnMode::STORAGE->value ? $storagePublicUrl : $originAssetUrl,
            'origin_asset_url' => $originAssetUrl,
            'storage_provider' => $this->nullableString(env('CDN_STORAGE_PROVIDER', 'dogecloud')) ?? 'dogecloud',
            'storage_access_key' => $this->nullableString(env('CDN_STORAGE_ACCESS_KEY', env('AWS_ACCESS_KEY_ID'))),
            'storage_secret_key' => $this->nullableString(env('CDN_STORAGE_SECRET_KEY', env('AWS_SECRET_ACCESS_KEY'))),
            'storage_bucket' => $this->nullableString(env('CDN_STORAGE_BUCKET', env('AWS_BUCKET'))),
            'storage_region' => $this->nullableString(env('CDN_STORAGE_REGION', env('AWS_DEFAULT_REGION', 'auto'))) ?? 'auto',
            'storage_endpoint' => $this->normalizeUrl(env('CDN_STORAGE_ENDPOINT', env('AWS_ENDPOINT'))),
            'storage_public_url' => $storagePublicUrl,
            'sync_enabled' => $this->normalizeBoolean(env('CDN_SYNC_ENABLED', false), false),
            'sync_on_build' => $this->normalizeBoolean(env('CDN_SYNC_ON_BUILD', true), true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeCdnAppliedSnapshot(?string $snapshot): array
    {
        if (! filled($snapshot)) {
            return [];
        }

        $decoded = json_decode($snapshot, true);

        return is_array($decoded) ? $this->normalizeCdnSnapshot($decoded) : [];
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

    private function shiftStoredTimestamps(string $fromTimezone, string $toTimezone): void
    {
        if ($fromTimezone === $toTimezone) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $this->shiftStoredTimestampsForPostgres($fromTimezone, $toTimezone);

            return;
        }

        if ($driver === 'sqlite') {
            $this->shiftStoredTimestampsForSqlite($fromTimezone, $toTimezone);
        }
    }

    private function shiftStoredTimestampsForPostgres(string $fromTimezone, string $toTimezone): void
    {
        foreach ($this->postgresTimestampColumnsByTable() as $table => $columns) {
            $assignments = [];
            $bindings = [];

            foreach ($columns as $column) {
                $quotedColumn = $this->quoteIdentifier($column);
                $assignments[] = "{$quotedColumn} = case when {$quotedColumn} is null then null else ({$quotedColumn} AT TIME ZONE ? AT TIME ZONE ?) end";
                $bindings[] = $fromTimezone;
                $bindings[] = $toTimezone;
            }

            if ($assignments === []) {
                continue;
            }

            DB::statement(
                'UPDATE '.$this->quoteIdentifier($table).' SET '.implode(', ', $assignments),
                $bindings,
            );
        }
    }

    private function shiftStoredTimestampsForSqlite(string $fromTimezone, string $toTimezone): void
    {
        foreach ($this->sqliteTimestampColumnsByTable() as $table => $columns) {
            $rows = DB::table($table)
                ->select(array_merge([DB::raw('rowid as _rowid')], $columns))
                ->get();

            foreach ($rows as $row) {
                $updates = [];

                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;

                    if ($value === null || $value === '') {
                        continue;
                    }

                    $updates[$column] = CarbonImmutable::parse((string) $value, $fromTimezone)
                        ->setTimezone($toTimezone)
                        ->format('Y-m-d H:i:s');
                }

                if ($updates === []) {
                    continue;
                }

                DB::table($table)
                    ->whereRaw('rowid = ?', [$row->_rowid])
                    ->update($updates);
            }
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function postgresTimestampColumnsByTable(): array
    {
        $rows = DB::select("
            select table_name, column_name
            from information_schema.columns
            where table_schema = current_schema()
              and data_type = 'timestamp without time zone'
              and table_name <> 'migrations'
            order by table_name, ordinal_position
        ");

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row->table_name][] = $row->column_name;
        }

        return $grouped;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function sqliteTimestampColumnsByTable(): array
    {
        $tables = DB::select("
            select name
            from sqlite_master
            where type = 'table'
              and name not like 'sqlite_%'
              and name <> 'migrations'
            order by name
        ");

        $grouped = [];

        foreach ($tables as $table) {
            $columns = DB::select('pragma table_info('.$this->quoteSqliteIdentifier($table->name).')');

            foreach ($columns as $column) {
                $type = strtolower((string) ($column->type ?? ''));

                if (str_contains($type, 'timestamp') || str_contains($type, 'datetime')) {
                    $grouped[$table->name][] = $column->name;
                }
            }
        }

        return $grouped;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function quoteSqliteIdentifier(string $identifier): string
    {
        return "'".str_replace("'", "''", $identifier)."'";
    }

    private function timezoneLabel(string $identifier, ?string $displayName, DateTimeImmutable $now): string
    {
        $timezone = new DateTimeZone($identifier);
        $offsetSeconds = $timezone->getOffset($now);
        $hours = intdiv(abs($offsetSeconds), 3600);
        $minutes = intdiv(abs($offsetSeconds) % 3600, 60);
        $offset = sprintf('UTC%s%02d:%02d', $offsetSeconds >= 0 ? '+' : '-', $hours, $minutes);
        $name = $displayName !== null ? $displayName.' · ' : '';

        return "{$name}{$identifier} ({$offset})";
    }
}
