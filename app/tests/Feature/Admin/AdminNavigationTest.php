<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_menu_follows_expected_information_order(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk();

        $content = $response->getContent();

        $labels = [
            '站点设置',
            'CDN 设置',
            '管理文章',
            '管理频道',
            '管理用户',
            '订阅设置',
            'DevTools',
            '退出登录',
        ];

        $positions = [];

        foreach ($labels as $label) {
            $position = mb_strpos($content, $label);

            $this->assertNotFalse($position, sprintf('未找到菜单项：%s', $label));

            $positions[$label] = $position;
        }

        for ($index = 1; $index < count($labels); $index++) {
            $previous = $labels[$index - 1];
            $current = $labels[$index];

            $this->assertTrue(
                $positions[$previous] < $positions[$current],
                sprintf('菜单顺序错误：%s 应位于 %s 之前', $previous, $current),
            );
        }
    }

    public function test_admin_root_redirects_to_site_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertRedirect(route('admin.site-settings.edit'));
    }

    public function test_authenticated_user_menu_uses_clickable_dropdown_markup(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-user-menu-shell', false);
        $response->assertSee('data-user-menu-trigger', false);
        $response->assertSee('data-user-menu-panel', false);
        $response->assertSee('aria-haspopup="menu"', false);
        $response->assertSee('aria-expanded="false"', false);
        $response->assertSee('hidden aria-hidden="true"', false);
    }
}
