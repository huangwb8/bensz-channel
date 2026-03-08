<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\User;
use App\Support\MarkdownRenderer;
use App\Support\StaticPageBuilder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $markdown = app(MarkdownRenderer::class);

        $admin = User::query()->updateOrCreate([
            'email' => config('community.admin.email'),
        ], [
            'name' => config('community.admin.name'),
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make(config('community.admin.password')),
            'email_verified_at' => now(),
            'last_seen_at' => now(),
        ]);

        $member = User::query()->updateOrCreate([
            'email' => 'member@example.com',
        ], [
            'name' => '社区成员',
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(),
            'phone' => '13800138000',
            'phone_verified_at' => now(),
            'password' => Hash::make('member123456'),
        ]);

        $channels = collect([
            ['name' => '公告大厅', 'slug' => 'announcements', 'description' => '管理员发布公告、版本说明与平台动态。', 'accent_color' => '#8b5cf6', 'icon' => '📢', 'sort_order' => 1],
            ['name' => '开发交流', 'slug' => 'engineering', 'description' => '围绕产品、架构、Docker 部署与开发实践进行讨论。', 'accent_color' => '#06b6d4', 'icon' => '🛠️', 'sort_order' => 2],
            ['name' => '资源分享', 'slug' => 'resources', 'description' => '沉淀教程、脚本、模板与精选内容。', 'accent_color' => '#10b981', 'icon' => '📚', 'sort_order' => 3],
            ['name' => '反馈建议', 'slug' => 'feedback', 'description' => '收集体验反馈、需求建议与问题报告。', 'accent_color' => '#f59e0b', 'icon' => '💬', 'sort_order' => 4],
        ])->map(fn (array $payload) => Channel::query()->updateOrCreate(['slug' => $payload['slug']], $payload));

        $articles = [
            [
                'channel' => 'announcements',
                'title' => '欢迎来到 Bensz Channel',
                'slug' => 'welcome-to-bensz-channel',
                'body' => <<<MD
# 欢迎加入

这是一个基于 **PHP + PostgreSQL + Docker** 构建的频道式社区原型。

## 当前已开放

- 频道导航与内容分区
- 管理员 Markdown 发文
- 登录用户 Markdown 评论
- 邮箱 / 手机验证码登录
- 微信 / QQ 演示扫码登录
- 游客静态 HTML + Gzip 访问

欢迎继续体验并提出建议。
MD,
                'gradient' => 'from-violet-500 via-fuchsia-500 to-sky-500',
            ],
            [
                'channel' => 'engineering',
                'title' => '部署架构说明：Laravel + Nginx + PostgreSQL',
                'slug' => 'deployment-architecture-overview',
                'body' => <<<MD
## 架构概览

当前部署采用单应用容器内运行 **Nginx + PHP-FPM**，并由 Docker Compose 编排以下组件：

1. `web`：Laravel 应用、静态页面构建、Nginx 入口
2. `postgres`：业务数据持久化
3. `redis`：缓存与高频读取优化
4. `mailpit`：本地邮件预览

### 游客访问路径

游客优先命中构建后的静态页面，登录用户则回退到 Laravel 动态响应。
MD,
                'gradient' => 'from-cyan-500 via-sky-500 to-indigo-500',
            ],
        ];

        foreach ($articles as $payload) {
            $channel = $channels->firstWhere('slug', $payload['channel']);

            $article = Article::query()->updateOrCreate([
                'slug' => $payload['slug'],
            ], [
                'channel_id' => $channel->id,
                'author_id' => $admin->id,
                'title' => $payload['title'],
                'excerpt' => $markdown->excerpt($payload['body']),
                'markdown_body' => $payload['body'],
                'html_body' => $markdown->toHtml($payload['body']),
                'is_published' => true,
                'published_at' => now()->subMinutes(20),
                'cover_gradient' => $payload['gradient'],
            ]);

            Comment::query()->updateOrCreate([
                'article_id' => $article->id,
                'user_id' => $member->id,
            ], [
                'markdown_body' => '看起来不错，期待后续把更多频道能力也接进来。',
                'html_body' => $markdown->toHtml('看起来不错，期待后续把更多频道能力也接进来。'),
                'is_visible' => true,
            ]);

            $article->update([
                'comment_count' => $article->allComments()->count(),
            ]);
        }

        app(StaticPageBuilder::class)->buildAll();

        $this->command?->info('默认管理员账号：'.config('community.admin.email'));
        $this->command?->info('默认管理员密码：'.config('community.admin.password'));
        $this->command?->info('示例成员账号：member@example.com / member123456');
        $this->command?->info('示例手机号：13800138000（验证码登录，开发环境会显示预览码）');
    }
}
