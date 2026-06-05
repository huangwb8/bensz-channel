<?php

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicArticlePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_paginates_all_articles_from_newest_to_oldest(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel('公告', 'notice');

        for ($index = 1; $index <= 13; $index++) {
            $this->createArticle($admin, $channel, sprintf('全部文章 %02d', $index), $index);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('共 13 篇')
            ->assertSee('全部文章 01')
            ->assertSee('全部文章 12')
            ->assertDontSee('全部文章 13')
            ->assertSee('?page=2', false);

        $this->get(route('home', ['page' => 2]))
            ->assertOk()
            ->assertSee('第 2 / 2 页')
            ->assertSee('全部文章 13')
            ->assertDontSee('全部文章 01');
    }

    public function test_channel_page_paginates_older_articles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = $this->createChannel('开发交流', 'engineering');

        for ($index = 1; $index <= 21; $index++) {
            $this->createArticle($admin, $channel, sprintf('频道文章 %02d', $index), $index);
        }

        $this->get(route('channels.show', $channel))
            ->assertOk()
            ->assertSee('共 21 篇文章')
            ->assertSee('频道文章 01')
            ->assertSee('频道文章 20')
            ->assertDontSee('频道文章 21')
            ->assertSee('?page=2', false);

        $this->get(route('channels.show', [$channel, 'page' => 2]))
            ->assertOk()
            ->assertSee('第 2 / 2 页')
            ->assertSee('频道文章 21')
            ->assertDontSee('频道文章 01');
    }

    private function createChannel(string $name, string $slug): Channel
    {
        return Channel::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $name.'频道',
            'accent_color' => '#8b5cf6',
            'icon' => '📢',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);
    }

    private function createArticle(User $author, Channel $channel, string $title, int $ageInMinutes): Article
    {
        return Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $author->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'excerpt' => $title.'摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'is_pinned' => false,
            'is_featured' => false,
            'published_at' => now()->subMinutes($ageInMinutes),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);
    }
}
