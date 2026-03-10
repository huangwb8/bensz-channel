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
        $response->assertSee('data-mobile-channel-trigger', false);
        $response->assertSee('id="mobile-channel-drawer"', false);
        $response->assertSee('显示频道');
        $response->assertDontSee('隐藏频道');
        $response->assertDontSee('未分类');
    }

    public function test_current_channel_is_marked_in_desktop_and_mobile_navigation(): void
    {
        $channel = Channel::query()->create([
            'name' => '开发交流',
            'slug' => 'engineering',
            'description' => '开发讨论',
            'icon' => '🛠️',
            'accent_color' => '#06b6d4',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $response = $this->get(route('channels.show', $channel));

        $response->assertOk();
        $response->assertSee('aria-current="page"', false);
        $response->assertSee('打开频道列表');
        $response->assertSee('title="打开频道列表"', false);
        $response->assertSee('title="关闭频道列表"', false);
        $response->assertSee('移动端频道较多时，可在这里快速选择。');
        $response->assertSee('title="点击复制 RSS 订阅链接"', false);
    }

    public function test_mobile_channel_drawer_is_rendered_outside_sticky_header(): void
    {
        $channel = Channel::query()->create([
            'name' => '开发交流',
            'slug' => 'engineering',
            'description' => '开发讨论',
            'icon' => '🛠️',
            'accent_color' => '#06b6d4',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $response = $this->get(route('channels.show', $channel));

        $response->assertOk();
        $response->assertSeeInOrder([
            '</header>',
            'id="mobile-channel-drawer"',
        ], false);
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
