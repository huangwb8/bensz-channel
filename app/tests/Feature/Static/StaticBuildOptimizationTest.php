<?php

namespace Tests\Feature\Static;

use App\Jobs\ProcessStaticSiteBuildJob;
use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use App\Support\StaticPageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class StaticBuildOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_incremental_article_rebuild_updates_changed_article_without_rewriting_unrelated_article(): void
    {
        config([
            'community.static.enabled' => true,
            'community.static.async' => false,
            'community.static.output_dir' => 'static',
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $primaryChannel = $this->createChannel('主频道', 'primary', 1);
        $secondaryChannel = $this->createChannel('次频道', 'secondary', 2);

        $targetArticle = $this->createArticle($admin, $primaryChannel, '旧标题', 'old-target-slug');
        $otherArticle = $this->createArticle($admin, $secondaryChannel, '无关文章', 'other-article');

        $builder = app(StaticPageBuilder::class);
        $builder->buildAll();

        $targetPath = public_path(sprintf('static/channels/%s/articles/%s/index.html', $primaryChannel->public_id, $targetArticle->public_id));
        $otherPath = public_path(sprintf('static/channels/%s/articles/%s/index.html', $secondaryChannel->public_id, $otherArticle->public_id));

        $this->assertFileExists($targetPath);
        $this->assertFileExists($otherPath);

        clearstatcache();
        $otherMtime = filemtime($otherPath);

        $before = $builder->captureArticleState($targetArticle->fresh(['channel']));

        sleep(1);

        $targetArticle->update([
            'title' => '新标题',
            'slug' => 'new-target-slug',
            'markdown_body' => "## 新章节\n\n更新后的正文",
            'html_body' => '<h2>新章节</h2><p>更新后的正文</p>',
        ]);

        $builder->rebuildArticle($targetArticle->fresh(['channel']), $before);

        $this->assertFileExists($targetPath);
        $this->assertStringContainsString('新标题', (string) file_get_contents($targetPath));

        clearstatcache();
        $this->assertSame($otherMtime, filemtime($otherPath));
    }

    public function test_async_build_command_dispatches_static_build_job(): void
    {
        config([
            'community.static.enabled' => true,
            'community.static.async' => true,
        ]);

        Bus::fake();

        $this->artisan('site:build-static --async')
            ->expectsOutput('静态页面构建任务已加入队列。')
            ->assertSuccessful();

        Bus::assertDispatched(ProcessStaticSiteBuildJob::class);
    }

    private function createChannel(string $name, string $slug, int $sortOrder): Channel
    {
        return Channel::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $name.' 描述',
            'accent_color' => '#8b5cf6',
            'icon' => '📢',
            'sort_order' => $sortOrder,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);
    }

    private function createArticle(User $author, Channel $channel, string $title, string $slug): Article
    {
        return Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $author->id,
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $title.' 摘要',
            'markdown_body' => "## {$title}\n\n正文",
            'html_body' => '<h2>'.$title.'</h2><p>正文</p>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);
    }
}
