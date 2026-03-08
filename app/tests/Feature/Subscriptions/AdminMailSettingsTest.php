<?php

namespace Tests\Feature\Subscriptions;

use App\Models\MailSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_smtp_configuration_section_on_subscription_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('settings.subscriptions.edit'))
            ->assertOk()
            ->assertSee('管理员 SMTP 配置')
            ->assertSee('SMTP 服务器')
            ->assertSee('发件邮箱');
    }

    public function test_admin_can_update_smtp_configuration(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->put(route('settings.subscriptions.mail.update'), [
                'enabled' => '1',
                'smtp_scheme' => 'tls',
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 587,
                'smtp_username' => 'mailer@example.com',
                'smtp_password' => 'smtp-secret',
                'from_address' => 'noreply@example.com',
                'from_name' => 'Bensz Channel Mailer',
            ])
            ->assertRedirect(route('settings.subscriptions.edit'));

        $setting = MailSetting::query()->first();

        $this->assertNotNull($setting);
        $this->assertTrue($setting->enabled);
        $this->assertSame('tls', $setting->smtp_scheme);
        $this->assertSame('smtp.example.com', $setting->smtp_host);
        $this->assertSame(587, $setting->smtp_port);
        $this->assertSame('mailer@example.com', $setting->smtp_username);
        $this->assertSame('smtp-secret', $setting->smtp_password);
        $this->assertSame('noreply@example.com', $setting->from_address);
        $this->assertSame('Bensz Channel Mailer', $setting->from_name);
    }

    public function test_member_cannot_update_smtp_configuration(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->put(route('settings.subscriptions.mail.update'), [
                'enabled' => '1',
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 587,
                'from_address' => 'noreply@example.com',
                'from_name' => 'Bensz Channel Mailer',
            ])
            ->assertForbidden();
    }
}
