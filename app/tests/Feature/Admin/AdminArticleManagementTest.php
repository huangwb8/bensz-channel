<?php

namespace Tests\Feature\Admin;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('admin.articles.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_article(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::query()->create([
            'name' => '公告',
            'slug' => 'notice',
            'description' => '公告频道',
            'accent_color' => '#8b5cf6',
            'icon' => '📢',
            'sort_order' => 1,
            'is_public' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.articles.store'), [
                'channel_id' => $channel->id,
                'title' => '后台发文测试',
                'slug' => 'admin-post-test',
                'excerpt' => '摘要',
                'markdown_body' => "# 标题\n\n正文内容",
                'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
                'published_at' => now()->format('Y-m-d H:i:s'),
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseHas('articles', [
            'slug' => 'admin-post-test',
            'title' => '后台发文测试',
        ]);
    }
}
