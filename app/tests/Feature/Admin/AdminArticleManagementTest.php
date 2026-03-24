<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Tag;
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
        $channel = $this->createChannel();
        $laravelTag = $this->createTag('Laravel', 'laravel');
        $releaseTag = $this->createTag('发布公告', 'release-note');

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
                'is_pinned' => 1,
                'is_featured' => 1,
                'tag_ids' => [$laravelTag->id, $releaseTag->id],
            ])
            ->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseHas('articles', [
            'slug' => 'admin-post-test',
            'title' => '后台发文测试',
            'is_pinned' => true,
            'is_featured' => true,
        ]);

        $this->assertDatabaseHas('article_tag', [
            'article_id' => Article::query()->where('slug', 'admin-post-test')->value('id'),
            'tag_id' => $laravelTag->id,
        ]);

        $this->assertDatabaseHas('article_tag', [
            'article_id' => Article::query()->where('slug', 'admin-post-test')->value('id'),
            'tag_id' => $releaseTag->id,
        ]);
    }

    public function test_admin_article_form_renders_clipboard_image_upload_hint(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->createChannel();

        $this->actingAs($admin)
            ->get(route('admin.articles.create'))
            ->assertOk()
            ->assertSee(route('uploads.images.store'), false)
            ->assertSee(route('uploads.videos.store'), false)
            ->assertSee('可直接按 <kbd>Ctrl</kbd> + <kbd>V</kbd> 粘贴图片或不大于 500MB 的视频', false);
    }

    public function test_admin_cannot_use_featured_channel_as_article_primary_channel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $featuredChannel = $this->createFeaturedChannel();

        $this->actingAs($admin)
            ->from(route('admin.articles.create'))
            ->post(route('admin.articles.store'), [
                'channel_id' => $featuredChannel->id,
                'title' => '错误频道文章',
                'slug' => 'invalid-featured-primary-channel',
                'excerpt' => '摘要',
                'markdown_body' => "# 标题\n\n正文内容",
                'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
                'published_at' => now()->format('Y-m-d H:i:s'),
                'is_published' => 1,
                'is_featured' => 1,
            ])
            ->assertRedirect(route('admin.articles.create'))
            ->assertSessionHasErrors(['channel_id']);

        $this->assertDatabaseMissing('articles', [
            'slug' => 'invalid-featured-primary-channel',
        ]);
    }

    public function test_admin_article_form_hides_featured_channel_from_primary_channel_options(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $featuredChannel = $this->createFeaturedChannel();
        $regularChannel = $this->createChannel();

        $this->actingAs($admin)
            ->get(route('admin.articles.create'))
            ->assertOk()
            ->assertDontSee('<option value="'.$featuredChannel->id.'"', false)
            ->assertSee('<option value="'.$regularChannel->id.'"', false)
            ->assertSee('精华频道只负责聚合展示', false);
    }

    public function test_admin_article_edit_page_is_marked_noindex(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel();
        $article = $this->createArticle($admin, $channel);

        $this->actingAs($admin)
            ->get(route('admin.articles.edit', $article))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('<link rel="canonical"', false)
            ->assertDontSee('"@type":"Article"', false);
    }

    public function test_admin_can_update_article(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel();
        $oldTag = $this->createTag('旧标签', 'legacy-tag');
        $newTag = $this->createTag('新标签', 'new-tag');
        $article = $this->createArticle($admin, $channel, [
            'title' => '旧标题',
            'slug' => 'old-title',
        ]);
        $article->tags()->attach($oldTag);

        $this->actingAs($admin)
            ->put(route('admin.articles.update', $article), [
                'channel_id' => $channel->id,
                'title' => '新标题',
                'slug' => 'new-title',
                'excerpt' => '新的摘要',
                'markdown_body' => "# 新标题\n\n新的正文内容",
                'cover_gradient' => 'from-sky-500 via-blue-500 to-indigo-500',
                'published_at' => now()->format('Y-m-d H:i:s'),
                'is_published' => 1,
                'is_pinned' => 1,
                'is_featured' => 1,
                'tag_ids' => [$newTag->id],
            ])
            ->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'slug' => 'new-title',
            'title' => '新标题',
            'excerpt' => '新的摘要',
            'is_pinned' => true,
            'is_featured' => true,
        ]);

        $this->assertDatabaseMissing('article_tag', [
            'article_id' => $article->id,
            'tag_id' => $oldTag->id,
        ]);

        $this->assertDatabaseHas('article_tag', [
            'article_id' => $article->id,
            'tag_id' => $newTag->id,
        ]);
    }

    public function test_admin_can_delete_article(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel();
        $article = $this->createArticle($admin, $channel);

        $this->actingAs($admin)
            ->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseMissing('articles', [
            'id' => $article->id,
        ]);
    }

    public function test_admin_can_bulk_delete_selected_articles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel();
        $articleA = $this->createArticle($admin, $channel, [
            'title' => '批量删除文章 A',
            'slug' => 'bulk-delete-article-a',
        ]);
        $articleB = $this->createArticle($admin, $channel, [
            'title' => '批量删除文章 B',
            'slug' => 'bulk-delete-article-b',
        ]);
        $articleC = $this->createArticle($admin, $channel, [
            'title' => '保留文章',
            'slug' => 'bulk-delete-article-c',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.articles.index'))
            ->delete(route('admin.articles.bulk-destroy'), [
                'selected_article_ids' => [$articleA->id, $articleB->id],
            ])
            ->assertRedirect(route('admin.articles.index'))
            ->assertSessionHas('status', '已删除 2 篇文章。');

        $this->assertDatabaseMissing('articles', ['id' => $articleA->id]);
        $this->assertDatabaseMissing('articles', ['id' => $articleB->id]);
        $this->assertDatabaseHas('articles', ['id' => $articleC->id]);
    }

    public function test_admin_bulk_delete_requires_selected_articles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->from(route('admin.articles.index'))
            ->delete(route('admin.articles.bulk-destroy'), [
                'selected_article_ids' => [],
            ])
            ->assertRedirect(route('admin.articles.index'))
            ->assertSessionHas('status', '请先选择要删除的文章。');
    }

    public function test_admin_article_index_renders_icon_actions_with_tooltips(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel();
        $article = $this->createArticle($admin, $channel, [
            'title' => '图标按钮文章',
            'slug' => 'icon-action-article',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.articles.index'))
            ->assertOk()
            ->assertSee('icon-action', false)
            ->assertSee('title="查看文章"', false)
            ->assertSee('title="编辑文章"', false)
            ->assertSee('title="删除文章"', false)
            ->assertSee('aria-label="编辑文章：'.$article->title.'"', false);
    }

    public function test_article_show_renders_quick_edit_action_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel();
        $article = $this->createArticle($admin, $channel, [
            'title' => '详情页快捷编辑文章',
            'slug' => 'article-show-quick-edit',
        ]);

        $this->actingAs($admin)
            ->get(route('articles.show', [$channel, $article]))
            ->assertOk()
            ->assertSee(route('admin.articles.edit', $article), false)
            ->assertSee('title="编辑文章"', false)
            ->assertSee('aria-label="编辑文章：'.$article->title.'"', false);
    }

    public function test_article_show_hides_quick_edit_action_for_non_admin_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $channel = $this->createChannel();
        $article = $this->createArticle($admin, $channel, [
            'title' => '普通成员不可见快捷编辑',
            'slug' => 'article-show-no-member-edit',
        ]);

        $this->actingAs($member)
            ->get(route('articles.show', [$channel, $article]))
            ->assertOk()
            ->assertDontSee(route('admin.articles.edit', $article), false)
            ->assertDontSee('aria-label="编辑文章：'.$article->title.'"', false);
    }

    public function test_admin_article_index_renders_bulk_delete_controls(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel();

        $this->createArticle($admin, $channel, [
            'title' => '批量控件文章',
            'slug' => 'bulk-controls-article',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.articles.index'))
            ->assertOk()
            ->assertSee('id="bulk-delete-form"', false)
            ->assertSee('全选当前页文章', false)
            ->assertSee('data-bulk-selected-count', false)
            ->assertSee('data-bulk-select-item', false)
            ->assertSee('批量删除', false);
    }

    public function test_admin_can_toggle_article_pinned_and_featured_flags_from_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel();
        $article = $this->createArticle($admin, $channel, [
            'is_pinned' => false,
            'is_featured' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.articles.pin', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertTrue($article->fresh()->is_pinned);

        $this->actingAs($admin)
            ->patch(route('admin.articles.feature', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertTrue($article->fresh()->is_featured);

        $this->actingAs($admin)
            ->patch(route('admin.articles.pin', $article->fresh()))
            ->assertRedirect(route('admin.articles.index'));

        $this->actingAs($admin)
            ->patch(route('admin.articles.feature', $article->fresh()))
            ->assertRedirect(route('admin.articles.index'));

        $article->refresh();

        $this->assertFalse($article->is_pinned);
        $this->assertFalse($article->is_featured);
    }

    public function test_admin_article_index_renders_pinned_and_featured_toggle_actions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel();
        $article = $this->createArticle($admin, $channel, [
            'title' => '状态切换文章',
            'slug' => 'status-toggle-article',
            'is_pinned' => true,
            'is_featured' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.articles.index'))
            ->assertOk()
            ->assertSee('title="取消置顶"', false)
            ->assertSee('title="取消精华"', false)
            ->assertSee('aria-label="切换置顶：'.$article->title.'"', false)
            ->assertSee('aria-label="切换精华：'.$article->title.'"', false);
    }

    private function createChannel(): Channel
    {
        return Channel::query()->create([
            'name' => '公告',
            'slug' => 'notice',
            'description' => '公告频道',
            'accent_color' => '#8b5cf6',
            'icon' => '📢',
            'sort_order' => 1,
            'is_public' => true,
        ]);
    }

    private function createFeaturedChannel(): Channel
    {
        return Channel::query()->create([
            'name' => '精华',
            'slug' => Channel::SLUG_FEATURED,
            'description' => '站内精选内容与重点沉淀。',
            'accent_color' => '#f59e0b',
            'icon' => '⭐',
            'sort_order' => 0,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);
    }

    private function createArticle(User $admin, Channel $channel, array $overrides = []): Article
    {
        return Article::query()->create(array_merge([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '后台文章',
            'slug' => 'admin-article',
            'excerpt' => '文章摘要',
            'markdown_body' => "# 标题\n\n正文内容",
            'html_body' => '<h1>标题</h1><p>正文内容</p>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
            'is_pinned' => false,
            'is_featured' => false,
        ], $overrides));
    }

    private function createTag(string $name, string $slug): Tag
    {
        return Tag::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $name.' 描述',
        ]);
    }
}
