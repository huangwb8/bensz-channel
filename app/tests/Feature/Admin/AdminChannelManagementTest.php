<?php

namespace Tests\Feature\Admin;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminChannelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_channel_management_routes(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('admin.channels.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_iconified_channel_actions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::query()->create([
            'name' => '前端频道',
            'slug' => 'frontend',
            'description' => '前端讨论区',
            'icon' => '🎨',
            'accent_color' => '#3b82f6',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.channels.index'));

        $response->assertOk();
        $response->assertSee('title="保存频道"', false);
        $response->assertSee('aria-label="保存频道：'.$channel->name.'"', false);
        $response->assertSee('title="删除频道"', false);
        $response->assertSee('aria-label="删除频道：'.$channel->name.'"', false);
        $response->assertSee('form="delete-channel-'.$channel->id.'"', false);
    }

    public function test_admin_channel_management_shows_uncategorized_nav_toggle_without_delete_action(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $uncategorized = Channel::query()->create([
            'name' => '未分类',
            'slug' => 'uncategorized',
            'description' => '系统自动归类的文章将汇总在此。',
            'icon' => '📦',
            'accent_color' => '#64748b',
            'sort_order' => 999,
            'is_public' => true,
            'show_in_top_nav' => false,
        ]);

        $managed = Channel::query()->create([
            'name' => '后端频道',
            'slug' => 'backend',
            'description' => '后端讨论区',
            'icon' => '🛠️',
            'accent_color' => '#10b981',
            'sort_order' => 2,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.channels.index'));

        $response->assertOk();
        $response->assertSee('系统频道：精华默认展示并聚合精华内容，未分类默认隐藏但仍会自动接收迁移文章。');
        $response->assertSee('value="'.$managed->slug.'"', false);
        $response->assertSee('value="'.$uncategorized->slug.'"', false);
        $response->assertSee(route('admin.channels.update', $uncategorized), false);
        $response->assertDontSee('aria-label="删除频道：'.$uncategorized->name.'"', false);
        $response->assertDontSee('delete-channel-'.$uncategorized->id, false);
        $response->assertSee('name="show_in_top_nav"', false);
    }

    public function test_admin_can_store_and_update_top_nav_visibility(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.channels.store'), [
                'name' => '隐藏频道',
                'slug' => 'hidden-channel',
                'description' => '只在直达页展示',
                'icon' => '🙈',
                'accent_color' => '#334155',
                'sort_order' => 3,
                'show_in_top_nav' => '0',
            ])
            ->assertRedirect();

        $channel = Channel::query()->where('slug', 'hidden-channel')->firstOrFail();

        $this->assertFalse($channel->show_in_top_nav);

        $this->actingAs($admin)
            ->put(route('admin.channels.update', $channel), [
                'name' => '隐藏频道',
                'slug' => 'hidden-channel',
                'description' => '只在直达页展示',
                'icon' => '🙈',
                'accent_color' => '#334155',
                'sort_order' => 3,
                'show_in_top_nav' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($channel->fresh()->show_in_top_nav);
    }

    public function test_admin_can_toggle_uncategorized_channel_top_nav_visibility(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $uncategorized = Channel::query()->create([
            'name' => '未分类',
            'slug' => 'uncategorized',
            'description' => '系统自动归类的文章将汇总在此。',
            'icon' => '📦',
            'accent_color' => '#64748b',
            'sort_order' => 999,
            'is_public' => true,
            'show_in_top_nav' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.channels.update', $uncategorized), [
                'show_in_top_nav' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($uncategorized->fresh()->show_in_top_nav);
    }

    public function test_admin_can_reorder_channels_via_drag_payload(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $first = Channel::query()->create([
            'name' => '频道 A',
            'slug' => 'channel-a',
            'description' => '频道 A',
            'icon' => '🅰️',
            'accent_color' => '#3b82f6',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $second = Channel::query()->create([
            'name' => '频道 B',
            'slug' => 'channel-b',
            'description' => '频道 B',
            'icon' => '🅱️',
            'accent_color' => '#10b981',
            'sort_order' => 2,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.channels.reorder'), [
                'ordered_ids' => [$second->id, $first->id],
            ])
            ->assertRedirect();

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
    }
}
