<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDevtoolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_devtools_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.devtools.index'))
            ->assertOk()
            ->assertSee('DevTools 远程管理')
            ->assertSee('API 接入信息')
            ->assertSee('API 密钥')
            ->assertSee('连接记录');
    }

    public function test_devtools_page_renders_iconified_management_shortcuts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.devtools.index'))
            ->assertOk()
            ->assertSee('data-tooltip="文章管理"', false)
            ->assertSee('aria-label="文章管理"', false)
            ->assertSee('data-tooltip="频道管理"', false)
            ->assertSee('aria-label="频道管理"', false)
            ->assertSee('data-tooltip="用户管理"', false)
            ->assertSee('aria-label="用户管理"', false)
            ->assertDontSee('<a href="'.route('admin.articles.index').'" class="btn-secondary">文章管理</a>', false)
            ->assertDontSee('<a href="'.route('admin.channels.index').'" class="btn-secondary">频道管理</a>', false)
            ->assertDontSee('<a href="'.route('admin.users.index').'" class="btn-secondary">用户管理</a>', false);
    }
}
