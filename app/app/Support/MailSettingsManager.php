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
            'smtp_scheme' => $setting?->smtp_scheme ?? (config('mail.mailers.smtp.scheme') ?? ''),
            'smtp_host' => $setting?->smtp_host ?? (string) config('mail.mailers.smtp.host'),
            'smtp_port' => (int) ($setting?->smtp_port ?? config('mail.mailers.smtp.port')),
            'smtp_username' => $setting?->smtp_username ?? (string) config('mail.mailers.smtp.username'),
            'from_address' => $setting?->from_address ?? (string) config('mail.from.address'),
            'from_name' => $setting?->from_name ?? (string) config('mail.from.name'),
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

        $setting->fill([
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'smtp_scheme' => $this->nullableString($validated['smtp_scheme'] ?? null),
            'smtp_host' => $this->nullableString($validated['smtp_host'] ?? null),
            'smtp_port' => isset($validated['smtp_port']) && $validated['smtp_port'] !== '' ? (int) $validated['smtp_port'] : null,
            'smtp_username' => $this->nullableString($validated['smtp_username'] ?? null),
            'from_address' => $this->nullableString($validated['from_address'] ?? null),
            'from_name' => $this->nullableString($validated['from_name'] ?? null),
        ]);

        if (filled($validated['smtp_password'] ?? null)) {
            $setting->smtp_password = (string) $validated['smtp_password'];
        } elseif (! $setting->exists) {
            $setting->smtp_password = null;
        }

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

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $setting->smtp_scheme,
            'mail.mailers.smtp.host' => $setting->smtp_host,
            'mail.mailers.smtp.port' => $setting->smtp_port,
            'mail.mailers.smtp.username' => $setting->smtp_username,
            'mail.mailers.smtp.password' => $setting->smtp_password,
            'mail.from.address' => $setting->from_address,
            'mail.from.name' => $setting->from_name,
        ]);
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
}
