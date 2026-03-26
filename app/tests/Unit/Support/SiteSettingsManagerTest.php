<?php

namespace Tests\Unit\Support;

use App\Models\Article;
use App\Models\Channel;
use App\Models\SiteSetting;
use App\Support\SiteSettingsManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteSettingsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_configured_settings_updates_runtime_timezone_and_php_default_timezone(): void
    {
        $originalTimezone = date_default_timezone_get();

        try {
            config(['app.timezone' => 'UTC']);
            date_default_timezone_set('UTC');

            SiteSetting::query()->create([
                'timezone' => 'America/New_York',
            ]);

            $manager = app(SiteSettingsManager::class);
            $manager->forgetCached();
            $manager->applyConfiguredSettings();

            $this->assertSame('America/New_York', config('app.timezone'));
            $this->assertSame('America/New_York', date_default_timezone_get());
        } finally {
            config(['app.timezone' => $originalTimezone]);
            date_default_timezone_set($originalTimezone);
        }
    }

    public function test_save_shifts_existing_naive_timestamps_when_timezone_changes(): void
    {
        config(['app.timezone' => 'Asia/Shanghai']);
        date_default_timezone_set('Asia/Shanghai');

        $admin = User::factory()->create();
        $channel = Channel::query()->create([
            'name' => 'Timezone Shift Channel',
            'slug' => 'timezone-shift-channel',
            'description' => 'Timezone shift test channel.',
            'accent_color' => '#2563eb',
            'icon' => 'clock',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => 'Timezone Shift Article',
            'slug' => 'timezone-shift-article',
            'excerpt' => 'Timezone shift article.',
            'markdown_body' => 'Body',
            'html_body' => '<p>Body</p>',
            'is_published' => true,
            'published_at' => '2026-03-26 12:00:00',
            'cover_gradient' => 'from-blue-500 to-cyan-500',
            'comment_count' => 0,
        ]);

        app(SiteSettingsManager::class)->save([
            'timezone' => 'America/New_York',
        ]);

        $this->assertSame('2026-03-26 00:00:00', DB::table('articles')->value('published_at'));
    }
}
