<?php

use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\ChannelController as AdminChannelController;
use App\Http\Controllers\Admin\DevtoolsController as AdminDevtoolsController;
use App\Http\Controllers\Admin\SiteSettingsController as AdminSiteSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RssFeedController;
use App\Http\Controllers\SubscriptionSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/auth/code', [LoginController::class, 'sendCode'])->name('auth.code.send');
Route::post('/auth/verify', [LoginController::class, 'verifyCode'])->name('auth.code.verify');
Route::post('/auth/password', [LoginController::class, 'loginWithPassword'])->name('auth.password.login');
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

Route::middleware('auth')->group(function (): void {
    Route::post('/articles/{article}/comments', [CommentController::class, 'store'])->name('articles.comments.store');
    Route::get('/settings/account', [AccountSettingsController::class, 'edit'])->name('settings.account.edit');
    Route::put('/settings/account/profile', [AccountSettingsController::class, 'updateProfile'])->name('settings.account.profile.update');
    Route::put('/settings/account/password', [AccountSettingsController::class, 'updatePassword'])->name('settings.account.password.update');
    Route::get('/settings/subscriptions', [SubscriptionSettingsController::class, 'edit'])->name('settings.subscriptions.edit');
    Route::put('/settings/subscriptions', [SubscriptionSettingsController::class, 'update'])->name('settings.subscriptions.update');
    Route::put('/settings/subscriptions/mail', [SubscriptionSettingsController::class, 'updateMailSettings'])
        ->middleware('admin')
        ->name('settings.subscriptions.mail.update');
    Route::put('/settings/subscriptions/mail/test', [SubscriptionSettingsController::class, 'testMailSettings'])
        ->middleware('admin')
        ->name('settings.subscriptions.mail.test');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function (): void {
    Route::redirect('/', '/admin/site-settings');

    Route::get('/channels', [AdminChannelController::class, 'index'])->name('channels.index');
    Route::post('/channels', [AdminChannelController::class, 'store'])->name('channels.store');
    Route::put('/channels/{channel}', [AdminChannelController::class, 'update'])->name('channels.update');
    Route::post('/channels/reorder', [AdminChannelController::class, 'reorder'])->name('channels.reorder');
    Route::delete('/channels/{channel}', [AdminChannelController::class, 'destroy'])->name('channels.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

    Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [AdminArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
    Route::patch('/articles/{article}/pin', [AdminArticleController::class, 'togglePin'])->name('articles.pin');
    Route::patch('/articles/{article}/feature', [AdminArticleController::class, 'toggleFeature'])->name('articles.feature');
    Route::delete('/articles/{article}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');

    // DevTools — Vibe Coding remote management
    Route::get('/devtools', [AdminDevtoolsController::class, 'index'])->name('devtools.index');
    Route::post('/devtools/keys', [AdminDevtoolsController::class, 'createKey'])->name('devtools.keys.create');
    Route::post('/devtools/keys/{id}/revoke', [AdminDevtoolsController::class, 'revokeKey'])->name('devtools.keys.revoke');
    Route::delete('/devtools/connections/{id}', [AdminDevtoolsController::class, 'terminateConnection'])->name('devtools.connections.terminate');

    Route::get('/site-settings', [AdminSiteSettingsController::class, 'edit'])->name('site-settings.edit');
    Route::put('/site-settings', [AdminSiteSettingsController::class, 'update'])->name('site-settings.update');
});
