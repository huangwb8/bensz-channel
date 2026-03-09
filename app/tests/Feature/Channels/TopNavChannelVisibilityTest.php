<?php

namespace Tests\Feature\Channels;

use App\Models\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopNavChannelVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_top_nav_only_renders_channels_enabled_for_top_nav(): void
    {
        Channel::query()->create([
            'name' => '显示频道',
            'slug' => 'visible-channel',
            'description' => '显示在顶栏',
            'icon' => '✨',
            'accent_color' => '#8b5cf6',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        Channel::query()->create([
            'name' => '隐藏频道',
            'slug' => 'hidden-channel',
            'description' => '不显示在顶栏',
            'icon' => '🙈',
            'accent_color' => '#334155',
            'sort_order' => 2,
            'is_public' => true,
            'show_in_top_nav' => false,
        ]);

        Channel::query()->create([
            'name' => '未分类',
            'slug' => 'uncategorized',
            'description' => '系统自动归类的文章将汇总在此。',
            'icon' => '📦',
            'accent_color' => '#64748b',
            'sort_order' => 999,
            'is_public' => true,
            'show_in_top_nav' => false,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('显示频道');
        $response->assertDontSee('隐藏频道');
        $response->assertDontSee('未分类');
    }

    public function test_hidden_channel_remains_accessible_by_direct_route(): void
    {
        $channel = Channel::query()->create([
            'name' => '隐藏频道',
            'slug' => 'hidden-channel',
            'description' => '不显示在顶栏',
            'icon' => '🙈',
            'accent_color' => '#334155',
            'sort_order' => 2,
            'is_public' => true,
            'show_in_top_nav' => false,
        ]);

        $response = $this->get(route('channels.show', $channel));

        $response->assertOk();
        $response->assertSee('隐藏频道');
        $response->assertSee('不显示在顶栏');
    }
}
