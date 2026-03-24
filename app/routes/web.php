<?php

use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\ChannelController as AdminChannelController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\CdnSettingsController as AdminCdnSettingsController;
use App\Http\Controllers\Admin\DevtoolsController as AdminDevtoolsController;
use App\Http\Controllers\Admin\SiteSettingsController as AdminSiteSettingsController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentManagementController;
use App\Http\Controllers\CommentSubscriptionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\RssFeedController;
use App\Http\Controllers\SiteDiscoveryController;
use App\Http\Controllers\SubscriptionSettingsController;
use App\Http\Controllers\VideoUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/robots.txt', [SiteDiscoveryController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SiteDiscoveryController::class, 'sitemap'])->name('sitemap');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/auth/code', [LoginController::class, 'sendCode'])->name('auth.code.send');
Route::post('/auth/verify', [LoginController::class, 'verifyCode'])->name('auth.code.verify');
Route::post('/auth/password', [LoginController::class, 'loginWithPassword'])->name('auth.password.login');
Route::get('/auth/two-factor', [LoginController::class, 'showTwoFactorChallenge'])->name('auth.two-factor.challenge');
Route::post('/auth/two-factor', [LoginController::class, 'verifyTwoFactor'])
    ->middleware('throttle:6,1')
    ->name('auth.two-factor.verify');
Route::get('/auth/social/{provider}', [SocialLoginController::class, 'redirect'])->name('auth.social.redirect');
Route::get('/auth/social/{provider}/callback', [SocialLoginController::class, 'callback'])->name('auth.social.callback');
Route::post('/auth/qr/{provider}', [LoginController::class, 'startQr'])->name('auth.qr.start');
Route::get('/auth/qr/{qrLoginRequest}', [LoginController::class, 'showQr'])->name('auth.qr.show');
Route::get('/auth/qr/{qrLoginRequest}/status', [LoginController::class, 'status'])->name('auth.qr.status');
Route::get('/scan/{provider}/{qrLoginRequest}', [LoginController::class, 'showApproval'])->name('auth.qr.approve.show');
Route::post('/scan/{provider}/{qrLoginRequest}', [LoginController::class, 'approve'])->name('auth.qr.approve.store');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
Route::get('/channels/{channel}/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/feeds/articles.xml', [RssFeedController::class, 'all'])->name('feeds.articles');
Route::get('/feeds/channels/{channel}.xml', [RssFeedController::class, 'channel'])->name('feeds.channels.show');
Route::get('/feeds/tags/{tag}.xml', [RssFeedController::class, 'tag'])->name('feeds.tags.show');

Route::middleware(['auth', 'not-banned'])->group(function (): void {
    Route::post('/articles/{article}/comments', [CommentController::class, 'store'])->name('articles.comments.store');
    Route::delete('/comments/{comment}', [CommentManagementController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/subscriptions', [CommentSubscriptionController::class, 'store'])->name('comments.subscriptions.store');
    Route::delete('/comments/{comment}/subscriptions', [CommentSubscriptionController::class, 'destroy'])->name('comments.subscriptions.destroy');
    Route::post('/uploads/images', [ImageUploadController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('uploads.images.store');
    Route::post('/uploads/videos', [VideoUploadController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('uploads.videos.store');
    Route::get('/settings/account', [AccountSettingsController::class, 'edit'])->name('settings.account.edit');
    Route::put('/settings/account/profile', [AccountSettingsController::class, 'updateProfile'])->name('settings.account.profile.update');
    Route::put('/settings/account/password', [AccountSettingsController::class, 'updatePassword'])->name('settings.account.password.update');
    Route::post('/settings/account/two-factor', [AccountSettingsController::class, 'enableTwoFactor'])->name('settings.account.two-factor.enable');
    Route::delete('/settings/account/two-factor', [AccountSettingsController::class, 'disableTwoFactor'])->name('settings.account.two-factor.disable');
    Route::post('/settings/account/two-factor/recovery-codes', [AccountSettingsController::class, 'regenerateTwoFactorRecoveryCodes'])
        ->name('settings.account.two-factor.recovery-codes.regenerate');
    Route::get('/settings/subscriptions', [SubscriptionSettingsController::class, 'edit'])->name('settings.subscriptions.edit');
    Route::put('/settings/subscriptions', [SubscriptionSettingsController::class, 'update'])->name('settings.subscriptions.update');
    Route::put('/settings/subscriptions/mail', [SubscriptionSettingsController::class, 'updateMailSettings'])
        ->middleware('admin')
        ->name('settings.subscriptions.mail.update');
    Route::put('/settings/subscriptions/mail/test', [SubscriptionSettingsController::class, 'testMailSettings'])
        ->middleware('admin')
        ->name('settings.subscriptions.mail.test');
});

Route::prefix('admin')->middleware(['auth', 'not-banned', 'admin'])->name('admin.')->group(function (): void {
    Route::redirect('/', '/admin/site-settings');

    Route::get('/channels', [AdminChannelController::class, 'index'])->name('channels.index');
    Route::post('/channels', [AdminChannelController::class, 'store'])->name('channels.store');
    Route::put('/channels/{channel}', [AdminChannelController::class, 'update'])->name('channels.update');
    Route::post('/channels/reorder', [AdminChannelController::class, 'reorder'])->name('channels.reorder');
    Route::delete('/channels/{channel}', [AdminChannelController::class, 'destroy'])->name('channels.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::delete('/users', [AdminUserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
    Route::post('/users/{user}/unban', [AdminUserController::class, 'unban'])->name('users.unban');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [AdminArticleController::class, 'store'])->name('articles.store');
    Route::delete('/articles', [AdminArticleController::class, 'bulkDestroy'])->name('articles.bulk-destroy');
    Route::get('/articles/{article}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
    Route::patch('/articles/{article}/pin', [AdminArticleController::class, 'togglePin'])->name('articles.pin');
    Route::patch('/articles/{article}/feature', [AdminArticleController::class, 'toggleFeature'])->name('articles.feature');
    Route::delete('/articles/{article}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');

    Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
    Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
    Route::put('/tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');

    Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}/visibility', [AdminCommentController::class, 'updateVisibility'])->name('comments.visibility');
    Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');

    // DevTools — Vibe Coding remote management
    Route::get('/devtools', [AdminDevtoolsController::class, 'index'])->name('devtools.index');
    Route::post('/devtools/keys', [AdminDevtoolsController::class, 'createKey'])->name('devtools.keys.create');
    Route::post('/devtools/keys/{id}/revoke', [AdminDevtoolsController::class, 'revokeKey'])->name('devtools.keys.revoke');
    Route::delete('/devtools/connections/{id}', [AdminDevtoolsController::class, 'terminateConnection'])->name('devtools.connections.terminate');

    Route::get('/site-settings', [AdminSiteSettingsController::class, 'edit'])->name('site-settings.edit');
    Route::put('/site-settings', [AdminSiteSettingsController::class, 'update'])->name('site-settings.update');
    Route::get('/site-settings/backup', [AdminSiteSettingsController::class, 'downloadBackup'])->name('site-settings.backup.download');
    Route::post('/site-settings/backup/restore', [AdminSiteSettingsController::class, 'restoreBackup'])->name('site-settings.backup.restore');

    Route::get('/cdn-settings', [AdminCdnSettingsController::class, 'index'])->name('cdn-settings.index');
    Route::put('/cdn-settings', [AdminCdnSettingsController::class, 'update'])->name('cdn-settings.update');
    Route::post('/cdn-settings/test', [AdminCdnSettingsController::class, 'testConnection'])->name('cdn-settings.test');
    Route::post('/cdn-settings/apply', [AdminCdnSettingsController::class, 'apply'])->name('cdn-settings.apply');
    Route::post('/cdn-settings/stop', [AdminCdnSettingsController::class, 'stop'])->name('cdn-settings.stop');
    Route::get('/cdn-settings/diff', [AdminCdnSettingsController::class, 'diff'])->name('cdn-settings.diff');
    Route::post('/cdn-settings/sync', [AdminCdnSettingsController::class, 'sync'])->name('cdn-settings.sync');
    Route::delete('/cdn-settings/remote', [AdminCdnSettingsController::class, 'clearRemote'])->name('cdn-settings.remote.clear');
});
