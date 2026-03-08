<?php

use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\ChannelController as AdminChannelController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/auth/code', [LoginController::class, 'sendCode'])->name('auth.code.send');
Route::post('/auth/verify', [LoginController::class, 'verifyCode'])->name('auth.code.verify');
Route::post('/auth/qr/{provider}', [LoginController::class, 'startQr'])->name('auth.qr.start');
Route::get('/auth/qr/{qrLoginRequest}', [LoginController::class, 'showQr'])->name('auth.qr.show');
Route::get('/auth/qr/{qrLoginRequest}/status', [LoginController::class, 'status'])->name('auth.qr.status');
Route::get('/scan/{provider}/{qrLoginRequest}', [LoginController::class, 'showApproval'])->name('auth.qr.approve.show');
Route::post('/scan/{provider}/{qrLoginRequest}', [LoginController::class, 'approve'])->name('auth.qr.approve.store');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
Route::get('/channels/{channel}/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

Route::middleware('auth')->group(function (): void {
    Route::post('/articles/{article}/comments', [CommentController::class, 'store'])->name('articles.comments.store');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function (): void {
    Route::redirect('/', '/admin/articles');

    Route::get('/channels', [AdminChannelController::class, 'index'])->name('channels.index');
    Route::post('/channels', [AdminChannelController::class, 'store'])->name('channels.store');
    Route::put('/channels/{channel}', [AdminChannelController::class, 'update'])->name('channels.update');

    Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [AdminArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
});
