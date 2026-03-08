<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_site_settings_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.site-settings.edit'))
            ->assertOk()
            ->assertSee('站点设置')
            ->assertSee('APP_NAME')
            ->assertSee('SITE_NAME')
            ->assertSee('SITE_TAGLINE');
    }

    public function test_admin_can_update_site_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->put(route('admin.site-settings.update'), [
                'app_name' => 'Bensz Channel Admin',
                'site_name' => 'Bensz Community',
                'site_tagline' => '一个支持静态游客访问与成员互动的频道式社区。',
            ])
            ->assertRedirect(route('admin.site-settings.edit'));

        $this->assertDatabaseHas(SiteSetting::class, [
            'app_name' => 'Bensz Channel Admin',
            'site_name' => 'Bensz Community',
            'site_tagline' => '一个支持静态游客访问与成员互动的频道式社区。',
        ]);
    }

    public function test_member_cannot_access_site_settings_page(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('admin.site-settings.edit'))
            ->assertForbidden();
    }
}
