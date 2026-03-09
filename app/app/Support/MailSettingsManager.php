<?php

namespace App\Support;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MailSettingsManager
{
    private const CACHE_KEY = 'mail_settings.current';

    public function current(): ?MailSetting
    {
        if (! $this->tableExists()) {
            return null;
        }

        return Cache::rememberForever(self::CACHE_KEY, static fn (): ?MailSetting => MailSetting::query()->first());
    }

    public function formData(): array
    {
        $setting = $this->current();

        return [
            'enabled' => $setting?->enabled ?? false,
            'smtp_scheme' => $this->normalizeFormScheme($setting?->smtp_scheme ?? config('mail.mailers.smtp.scheme')),
            'smtp_host' => $setting?->smtp_host ?? (string) config('mail.mailers.smtp.host'),
            'smtp_port' => (int) ($setting?->smtp_port ?? config('mail.mailers.smtp.port')),
            'smtp_username' => $setting?->smtp_username ?? (string) config('mail.mailers.smtp.username'),
            'from_address' => $setting?->from_address ?? (string) config('mail.from.address'),
            'from_name' => $setting?->from_name ?? (string) config('mail.from.name'),
            'test_recipient' => $setting?->test_recipient,
        ];
    }

    public function hasStoredPassword(): bool
    {
        return filled($this->current()?->smtp_password);
    }

    public function usingCustomSettings(): bool
    {
        $setting = $this->current();

        return $setting instanceof MailSetting
            && $setting->enabled
            && $setting->isConfigured();
    }

    public function save(array $validated): MailSetting
    {
        $setting = MailSetting::query()->firstOrNew(['id' => 1]);
        $attributes = $this->draftSettings($validated);

        $setting->fill([
            'enabled' => $attributes['enabled'],
            'smtp_scheme' => $attributes['smtp_scheme'],
            'smtp_host' => $attributes['smtp_host'],
            'smtp_port' => $attributes['smtp_port'],
            'smtp_username' => $attributes['smtp_username'],
            'from_address' => $attributes['from_address'],
            'from_name' => $attributes['from_name'],
            'test_recipient' => $attributes['test_recipient'],
        ]);

        $setting->smtp_password = $attributes['smtp_password'];

        $setting->save();

        $this->forgetCached();

        return $setting->fresh();
    }

    public function applyConfiguredSettings(): void
    {
        $setting = $this->current();

        if (! $setting instanceof MailSetting || ! $setting->enabled || ! $setting->isConfigured()) {
            return;
        }

        $runtimeConfig = $this->runtimeConfigFor($setting->toArray(), false);

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $runtimeConfig['scheme'],
            'mail.mailers.smtp.host' => $runtimeConfig['host'],
            'mail.mailers.smtp.port' => $runtimeConfig['port'],
            'mail.mailers.smtp.username' => $runtimeConfig['username'],
            'mail.mailers.smtp.password' => $runtimeConfig['password'],
            'mail.from.address' => $runtimeConfig['from_address'],
            'mail.from.name' => $runtimeConfig['from_name'],
        ]);
    }

    public function runtimeConfigFor(array $validated, bool $reuseStoredPassword = true): array
    {
        $attributes = $this->draftSettings($validated, $reuseStoredPassword);

        return [
            'scheme' => $this->toRuntimeScheme($attributes['smtp_scheme']),
            'host' => $attributes['smtp_host'],
            'port' => $attributes['smtp_port'],
            'username' => $attributes['smtp_username'],
            'password' => $attributes['smtp_password'],
            'from_address' => $attributes['from_address'],
            'from_name' => $attributes['from_name'],
        ];
    }

    public function forgetCached(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('mail_settings');
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

    private function draftSettings(array $validated, bool $reuseStoredPassword = true): array
    {
        $currentPassword = $reuseStoredPassword ? $this->current()?->smtp_password : null;
        $providedPassword = $this->nullableString($validated['smtp_password'] ?? null);

        return [
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'smtp_scheme' => $this->normalizeFormScheme($validated['smtp_scheme'] ?? null),
            'smtp_host' => $this->nullableString($validated['smtp_host'] ?? null),
            'smtp_port' => isset($validated['smtp_port']) && $validated['smtp_port'] !== '' ? (int) $validated['smtp_port'] : null,
            'smtp_username' => $this->nullableString($validated['smtp_username'] ?? null),
            'smtp_password' => $providedPassword ?? $currentPassword,
            'from_address' => $this->nullableString($validated['from_address'] ?? null),
            'from_name' => $this->nullableString($validated['from_name'] ?? null),
            'test_recipient' => $this->nullableString($validated['test_recipient'] ?? null),
        ];
    }

    private function normalizeFormScheme(mixed $value): ?string
    {
        $scheme = strtolower((string) $this->nullableString($value));

        return match ($scheme) {
            'ssl', 'smtps' => 'ssl',
            'tls' => 'tls',
            default => null,
        };
    }

    private function toRuntimeScheme(?string $scheme): ?string
    {
        return match ($this->normalizeFormScheme($scheme)) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => null,
        };
    }
}
