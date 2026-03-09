<?php

use App\Http\Controllers\Api\Vibe\AgentController;
use App\Http\Controllers\Api\Vibe\ArticleController as VibeArticleController;
use App\Http\Controllers\Api\Vibe\ChannelController as VibeChannelController;
use App\Http\Controllers\Api\Vibe\CommentController as VibeCommentController;
use App\Http\Controllers\Api\Vibe\UserController as VibeUserController;
use Illuminate\Support\Facades\Route;

// Public ping — no auth required
Route::get('/vibe/ping', [AgentController::class, 'ping'])->name('vibe.ping');

// Authenticated Vibe Agent routes
Route::prefix('vibe')->middleware('vibe-api')->name('vibe.')->group(function (): void {
    // Connection lifecycle
    Route::post('/connect', [AgentController::class, 'connect'])->name('connect');
    Route::post('/heartbeat', [AgentController::class, 'heartbeat'])->name('heartbeat');
    Route::post('/disconnect', [AgentController::class, 'disconnect'])->name('disconnect');

    // Channels
    Route::get('/channels', [VibeChannelController::class, 'index'])->name('channels.index');
    Route::post('/channels', [VibeChannelController::class, 'store'])->name('channels.store');
    Route::put('/channels/{channel}', [VibeChannelController::class, 'update'])->name('channels.update');
    Route::delete('/channels/{channel}', [VibeChannelController::class, 'destroy'])->name('channels.destroy');

    // Articles
    Route::get('/articles', [VibeArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/{article}', [VibeArticleController::class, 'show'])->name('articles.show');
    Route::post('/articles', [VibeArticleController::class, 'store'])->name('articles.store');
    Route::put('/articles/{article}', [VibeArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article}', [VibeArticleController::class, 'destroy'])->name('articles.destroy');

    // Comments
    Route::get('/comments', [VibeCommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}', [VibeCommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [VibeCommentController::class, 'destroy'])->name('comments.destroy');

    // Users
    Route::get('/users', [VibeUserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}', [VibeUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [VibeUserController::class, 'destroy'])->name('users.destroy');
});
