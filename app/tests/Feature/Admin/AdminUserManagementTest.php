<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_management_index_shows_dashboard_batch_actions_and_icon_controls(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'last_seen_at' => now(),
        ]);
        $member = User::factory()->create([
            'name' => '可运营成员',
            'role' => User::ROLE_MEMBER,
            'last_seen_at' => now()->subDay(),
        ]);

        $channel = Channel::query()->create([
            'name' => '仪表盘测试频道',
            'slug' => 'dashboard-test-channel',
            'description' => '用于后台用户仪表盘测试。',
            'accent_color' => '#2563eb',
            'icon' => '📊',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $member->id,
            'title' => '成员已发布文章',
            'slug' => 'member-published-article',
            'excerpt' => '文章摘要',
            'markdown_body' => '文章正文',
            'html_body' => '<p>文章正文</p>',
            'is_published' => true,
            'is_pinned' => false,
            'is_featured' => false,
            'published_at' => now()->subHours(2),
            'cover_gradient' => 'from-sky-500 via-cyan-500 to-emerald-500',
            'comment_count' => 0,
        ]);

        $article = Article::query()->firstOrFail();

        Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $member->id,
            'markdown_body' => '成员评论',
            'html_body' => '<p>成员评论</p>',
            'is_visible' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('用户运营仪表盘')
            ->assertSee('最近 7 天登录 / 活跃分布')
            ->assertSee('批量删除')
            ->assertSee('data-bulk-selected-count', false)
            ->assertSee('title="保存用户"', false)
            ->assertSee('aria-label="保存用户：'.$member->name.'"', false)
            ->assertSee('title="删除用户"', false)
            ->assertSee('title="展开用户"', false)
            ->assertSee('aria-label="展开用户：'.$member->name.'"', false);
    }

    public function test_admin_user_dashboard_uses_theme_aware_classes_instead_of_forced_dark_mode(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('user-ops-dashboard-card', false)
            ->assertSee('user-ops-dashboard-panel', false)
            ->assertDontSee('bg-slate-950', false)
            ->assertDontSee('bg-slate-900/70', false);
    }

    public function test_non_admin_cannot_access_user_management_routes(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_member_profile_and_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create([
            'name' => '原始成员',
            'email' => 'member@example.com',
            'phone' => '13800000000',
            'role' => User::ROLE_MEMBER,
            'avatar_url' => 'https://cdn.example.com/old.png',
            'bio' => 'old bio',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'name' => '运营负责人',
                'email' => 'ops@example.com',
                'phone' => '13900000000',
                'role' => User::ROLE_ADMIN,
                'avatar_url' => 'https://cdn.example.com/avatar.png',
                'bio' => '负责社区运营与用户支持',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'name' => '运营负责人',
            'email' => 'ops@example.com',
            'phone' => '13900000000',
            'role' => User::ROLE_ADMIN,
            'avatar_url' => 'https://cdn.example.com/avatar.png',
            'bio' => '负责社区运营与用户支持',
        ]);
    }

    public function test_admin_can_delete_member_and_cleanup_related_runtime_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create([
            'name' => '待删除成员',
            'email' => 'member-delete@example.com',
            'phone' => '13800138008',
            'role' => User::ROLE_MEMBER,
        ]);
        $otherMember = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $channel = Channel::query()->create([
            'name' => '测试频道',
            'slug' => 'user-delete-channel',
            'description' => '用于用户删除测试。',
            'accent_color' => '#8b5cf6',
            'icon' => '🧪',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $memberArticle = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $member->id,
            'title' => '成员文章',
            'slug' => 'member-owned-article',
            'excerpt' => '成员文章摘要',
            'markdown_body' => '成员文章正文',
            'html_body' => '<p>成员文章正文</p>',
            'is_published' => true,
            'is_pinned' => false,
            'is_featured' => false,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
            'comment_count' => 0,
        ]);

        $sharedArticle = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '公共文章',
            'slug' => 'shared-article',
            'excerpt' => '公共文章摘要',
            'markdown_body' => '公共文章正文',
            'html_body' => '<p>公共文章正文</p>',
            'is_published' => true,
            'is_pinned' => false,
            'is_featured' => false,
            'published_at' => now(),
            'cover_gradient' => 'from-sky-500 via-cyan-500 to-emerald-500',
            'comment_count' => 2,
        ]);

        Comment::query()->create([
            'article_id' => $sharedArticle->id,
            'user_id' => $member->id,
            'markdown_body' => '待删除成员评论',
            'html_body' => '<p>待删除成员评论</p>',
            'is_visible' => true,
        ]);

        Comment::query()->create([
            'article_id' => $sharedArticle->id,
            'user_id' => $otherMember->id,
            'markdown_body' => '保留评论',
            'html_body' => '<p>保留评论</p>',
            'is_visible' => true,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $member->email,
            'token' => 'reset-token-for-member',
            'created_at' => now(),
        ]);

        DB::table('sessions')->insert([
            'id' => 'member-session-id',
            'user_id' => $member->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $member))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
        $this->assertDatabaseMissing('articles', ['id' => $memberArticle->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $member->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $member->email]);
        $this->assertSame(1, $sharedArticle->fresh()->comment_count);
    }

    public function test_admin_can_bulk_delete_selected_members_and_skip_admin_accounts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $managedAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $memberA = User::factory()->create([
            'name' => '批量删除成员 A',
            'email' => 'bulk-a@example.com',
            'role' => User::ROLE_MEMBER,
        ]);
        $memberB = User::factory()->create([
            'name' => '批量删除成员 B',
            'email' => 'bulk-b@example.com',
            'role' => User::ROLE_MEMBER,
        ]);
        $channel = Channel::query()->create([
            'name' => '批量删除频道',
            'slug' => 'bulk-delete-channel',
            'description' => '用于批量删除测试。',
            'accent_color' => '#14b8a6',
            'icon' => '📦',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $sharedArticle = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '批量删除公共文章',
            'slug' => 'bulk-shared-article',
            'excerpt' => '公共文章摘要',
            'markdown_body' => '公共文章正文',
            'html_body' => '<p>公共文章正文</p>',
            'is_published' => true,
            'is_pinned' => false,
            'is_featured' => false,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
            'comment_count' => 2,
        ]);

        foreach ([$memberA, $memberB] as $index => $member) {
            Article::query()->create([
                'channel_id' => $channel->id,
                'author_id' => $member->id,
                'title' => '成员文章 '.$index,
                'slug' => 'bulk-member-article-'.$index,
                'excerpt' => '成员文章摘要',
                'markdown_body' => '成员文章正文',
                'html_body' => '<p>成员文章正文</p>',
                'is_published' => true,
                'is_pinned' => false,
                'is_featured' => false,
                'published_at' => now(),
                'cover_gradient' => 'from-sky-500 via-cyan-500 to-emerald-500',
                'comment_count' => 0,
            ]);

            Comment::query()->create([
                'article_id' => $sharedArticle->id,
                'user_id' => $member->id,
                'markdown_body' => '待删除成员评论 '.$index,
                'html_body' => '<p>待删除成员评论 '.$index.'</p>',
                'is_visible' => true,
            ]);

            DB::table('password_reset_tokens')->insert([
                'email' => $member->email,
                'token' => 'reset-token-'.$index,
                'created_at' => now(),
            ]);

            DB::table('sessions')->insert([
                'id' => 'bulk-session-'.$index,
                'user_id' => $member->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'phpunit',
                'payload' => 'payload',
                'last_activity' => now()->timestamp,
            ]);
        }

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.bulk-destroy'), [
                'selected_user_ids' => [$memberA->id, $memberB->id, $managedAdmin->id],
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', '已删除 2 位普通用户。已自动跳过 1 位管理员账号。');

        $this->assertDatabaseMissing('users', ['id' => $memberA->id]);
        $this->assertDatabaseMissing('users', ['id' => $memberB->id]);
        $this->assertDatabaseHas('users', ['id' => $managedAdmin->id, 'role' => User::ROLE_ADMIN]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $memberA->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $memberB->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $memberA->email]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $memberB->email]);
        $this->assertSame(0, $sharedArticle->fresh()->comment_count);
    }

    public function test_admin_cannot_delete_admin_account_from_user_management(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $managedAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($superAdmin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $managedAdmin))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managedAdmin->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_last_admin_cannot_demote_self(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'role' => User::ROLE_MEMBER,
                'bio' => $admin->bio,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
