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
}
