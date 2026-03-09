<?php

namespace Tests\Feature\Subscriptions;

use App\Models\MailSetting;
use App\Models\User;
use App\Support\SmtpConnectivityTester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
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
            ->assertSee('发件邮箱')
            ->assertSee('测试收件邮箱')
            ->assertSee('测试 SMTP');
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
                'test_recipient' => 'verify@example.com',
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
        $this->assertSame('verify@example.com', $setting->test_recipient);
    }

    public function test_saved_test_recipient_is_shown_in_smtp_configuration_form(): void
    {
        MailSetting::query()->create([
            'enabled' => true,
            'smtp_scheme' => 'tls',
            'smtp_host' => 'smtp.saved.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'saved-user@example.com',
            'smtp_password' => 'saved-secret',
            'from_address' => 'saved@example.com',
            'from_name' => 'Saved Mailer',
            'test_recipient' => 'verify@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('settings.subscriptions.edit'))
            ->assertOk()
            ->assertSee('value="verify@example.com"', false);
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

    public function test_admin_can_test_smtp_configuration_without_saving_it(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->mock(SmtpConnectivityTester::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendTestMessage')
                ->once()
                ->withArgs(function (array $config, string $recipient): bool {
                    return $config['scheme'] === 'smtp'
                        && $config['host'] === 'smtp.example.com'
                        && $config['port'] === 587
                        && $config['username'] === 'mailer@example.com'
                        && $config['password'] === 'smtp-secret'
                        && $config['from_address'] === 'noreply@example.com'
                        && $config['from_name'] === 'Bensz Channel Mailer'
                        && $recipient === 'verify@example.com';
                });
        });

        $response = $this->actingAs($admin)
            ->from(route('settings.subscriptions.edit'))
            ->put(route('settings.subscriptions.mail.test'), [
                'smtp_scheme' => 'tls',
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 587,
                'smtp_username' => 'mailer@example.com',
                'smtp_password' => 'smtp-secret',
                'from_address' => 'noreply@example.com',
                'from_name' => 'Bensz Channel Mailer',
                'test_recipient' => 'verify@example.com',
            ]);

        $response
            ->assertRedirect(route('settings.subscriptions.edit'))
            ->assertSessionHas('status', 'SMTP 测试成功，已向 verify@example.com 发送测试邮件。请检查收件箱或 Mailpit。');

        $this->assertDatabaseCount('mail_settings', 0);
    }

    public function test_admin_smtp_test_reuses_existing_password_when_input_left_blank(): void
    {
        MailSetting::query()->create([
            'enabled' => true,
            'smtp_scheme' => 'tls',
            'smtp_host' => 'smtp.saved.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'saved-user@example.com',
            'smtp_password' => 'saved-secret',
            'from_address' => 'saved@example.com',
            'from_name' => 'Saved Mailer',
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->mock(SmtpConnectivityTester::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendTestMessage')
                ->once()
                ->withArgs(function (array $config, string $recipient): bool {
                    return $config['password'] === 'saved-secret'
                        && $recipient === 'admin@example.com';
                });
        });

        $this->actingAs($admin)
            ->from(route('settings.subscriptions.edit'))
            ->put(route('settings.subscriptions.mail.test'), [
                'smtp_scheme' => 'tls',
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 587,
                'smtp_username' => 'mailer@example.com',
                'smtp_password' => '',
                'from_address' => 'noreply@example.com',
                'from_name' => 'Bensz Channel Mailer',
                'test_recipient' => '',
            ])
            ->assertRedirect(route('settings.subscriptions.edit'));
    }

    public function test_admin_sees_test_error_when_smtp_probe_fails(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->mock(SmtpConnectivityTester::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendTestMessage')
                ->once()
                ->andThrow(new \RuntimeException('Connection refused'));
        });

        $this->actingAs($admin)
            ->from(route('settings.subscriptions.edit'))
            ->put(route('settings.subscriptions.mail.test'), [
                'smtp_scheme' => 'tls',
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 587,
                'smtp_username' => 'mailer@example.com',
                'smtp_password' => 'smtp-secret',
                'from_address' => 'noreply@example.com',
                'from_name' => 'Bensz Channel Mailer',
                'test_recipient' => 'verify@example.com',
            ])
            ->assertRedirect(route('settings.subscriptions.edit'))
            ->assertSessionHasErrors(['smtp_test']);

        $this->assertDatabaseCount('mail_settings', 0);
    }
}
