<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\Channel;
use App\Models\ChannelEmailSubscription;
use App\Models\Comment;
use App\Models\CommentSubscription;
use App\Models\MailSetting;
use App\Models\SiteSetting;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Support\DataBackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminDataBackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_restore_core_data_from_backup_archive(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => '原始管理员',
            'email' => 'admin@example.com',
        ]);
        $member = User::factory()->create([
            'name' => '原始成员',
            'email' => 'member@example.com',
        ]);

        SiteSetting::query()->create([
            'app_name' => 'Original App',
            'site_name' => 'Original Site',
            'site_tagline' => 'Original Tagline',
            'auth_enabled_methods' => ['email_code', 'qq_qr'],
            'cdn_asset_url' => 'https://cdn.original.test',
            'theme_mode' => 'dark',
            'theme_day_start' => '08:00',
            'theme_night_start' => '20:00',
        ]);

        MailSetting::query()->create([
            'enabled' => true,
            'smtp_scheme' => 'tls',
            'smtp_host' => 'smtp.original.test',
            'smtp_port' => 2525,
            'smtp_username' => 'original-user',
            'smtp_password' => 'original-secret',
            'from_address' => 'noreply@original.test',
            'from_name' => 'Original Mailer',
            'test_recipient' => 'test@original.test',
        ]);

        UserNotificationPreference::query()->where('user_id', $member->id)->update([
            'email_all_articles' => false,
            'email_mentions' => true,
            'email_comment_replies' => false,
        ]);

        SocialAccount::query()->create([
            'user_id' => $member->id,
            'provider' => 'qq',
            'provider_user_id' => 'qq-user-1',
            'profile_snapshot' => ['nickname' => 'QQ Member'],
        ]);

        $channel = Channel::query()->create([
            'name' => '原始频道',
            'slug' => 'original-channel',
            'description' => '原始频道描述',
            'accent_color' => '#7c3aed',
            'icon' => 'hash',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $member->id,
            'title' => '原始文章',
            'slug' => 'original-article',
            'excerpt' => '原始摘要',
            'markdown_body' => '原始 Markdown',
            'html_body' => '<p>原始 HTML</p>',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'cover_gradient' => 'from-blue-500 to-cyan-500',
            'comment_count' => 1,
        ]);

        $comment = Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $member->id,
            'markdown_body' => '原始评论',
            'html_body' => '<p>原始评论</p>',
            'is_visible' => true,
        ]);

        CommentSubscription::query()->updateOrCreate([
            'comment_id' => $comment->id,
            'user_id' => $member->id,
        ], [
            'is_active' => false,
        ]);

        ChannelEmailSubscription::query()->create([
            'user_id' => $member->id,
            'channel_id' => $channel->id,
        ]);

        $archivePath = app(DataBackupManager::class)->createBackupArchive();

        SiteSetting::query()->update([
            'app_name' => 'Mutated App',
            'site_name' => 'Mutated Site',
        ]);
        MailSetting::query()->update([
            'smtp_host' => 'smtp.mutated.test',
            'smtp_password' => 'mutated-secret',
        ]);
        CommentSubscription::query()->delete();
        Comment::query()->delete();
        Article::query()->delete();
        ChannelEmailSubscription::query()->delete();
        Channel::query()->delete();
        SocialAccount::query()->delete();
        UserNotificationPreference::query()->delete();
        User::query()->whereKey($member->id)->update([
            'name' => '被改坏的成员',
            'email' => 'broken@example.com',
        ]);
        User::factory()->create([
            'name' => '新增噪声用户',
            'email' => 'noise@example.com',
        ]);

        $upload = new UploadedFile(
            $archivePath,
            'core-backup.tar.gz',
            'application/gzip',
            null,
            true,
        );

        $response = $this->actingAs($admin)
            ->post(route('admin.site-settings.backup.restore'), [
                'backup_archive' => $upload,
            ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();

        $this->assertDatabaseHas(SiteSetting::class, [
            'app_name' => 'Original App',
            'site_name' => 'Original Site',
        ]);
        $mailSetting = MailSetting::query()->first();
        $this->assertNotNull($mailSetting);
        $this->assertSame('smtp.original.test', $mailSetting->smtp_host);
        $this->assertSame('original-secret', $mailSetting->smtp_password);
        $this->assertDatabaseHas(User::class, [
            'name' => '原始成员',
            'email' => 'member@example.com',
        ]);
        $this->assertDatabaseMissing(User::class, [
            'email' => 'noise@example.com',
        ]);
        $this->assertDatabaseHas(Channel::class, [
            'slug' => 'original-channel',
        ]);
        $this->assertDatabaseHas(Article::class, [
            'slug' => 'original-article',
            'title' => '原始文章',
        ]);
        $this->assertDatabaseHas(Comment::class, [
            'markdown_body' => '原始评论',
        ]);
        $this->assertDatabaseHas(CommentSubscription::class, [
            'comment_id' => $comment->id,
            'user_id' => $member->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas(SocialAccount::class, [
            'provider' => 'qq',
            'provider_user_id' => 'qq-user-1',
        ]);
        $this->assertDatabaseHas(ChannelEmailSubscription::class, [
            'user_id' => $member->id,
            'channel_id' => $channel->id,
        ]);
    }
}
