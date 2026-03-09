<?php

namespace Tests\Unit\Support;

use App\Models\MailSetting;
use App\Support\MailSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailSettingsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_configured_settings_maps_tls_to_smtp(): void
    {
        MailSetting::query()->create([
            'enabled' => true,
            'smtp_scheme' => 'tls',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'mailer@example.com',
            'smtp_password' => 'smtp-secret',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Bensz Channel Mailer',
        ]);

        $manager = app(MailSettingsManager::class);
        $manager->forgetCached();
        $manager->applyConfiguredSettings();

        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
    }

    public function test_apply_configured_settings_maps_ssl_to_smtps(): void
    {
        MailSetting::query()->create([
            'enabled' => true,
            'smtp_scheme' => 'ssl',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 465,
            'smtp_username' => 'mailer@example.com',
            'smtp_password' => 'smtp-secret',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Bensz Channel Mailer',
        ]);

        $manager = app(MailSettingsManager::class);
        $manager->forgetCached();
        $manager->applyConfiguredSettings();

        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
    }
}
