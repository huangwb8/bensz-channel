<?php

namespace App\Providers;

use App\Contracts\Auth\OtpAuthGateway;
use App\Models\Comment;
use App\Models\User;
use App\Services\Auth\BetterAuthGateway;
use App\Services\Auth\LegacyOtpGateway;
use App\Support\CanonicalUrlManager;
use App\Support\CommunityViewData;
use App\Support\MailSettingsManager;
use App\Support\Seo\SeoMetadataFactory;
use App\Support\SiteSettingsManager;
use App\Support\StableUserIdManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpAuthGateway::class, function ($app) {
            return config('community.auth.driver') === 'legacy'
                ? $app->make(LegacyOtpGateway::class)
                : $app->make(BetterAuthGateway::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(SiteSettingsManager::class)->applyConfiguredSettings();
        app(MailSettingsManager::class)->applyConfiguredSettings();
        app(CanonicalUrlManager::class)->apply();

        Queue::before(static function (): void {
            app(SiteSettingsManager::class)->applyConfiguredSettings();
        });

        User::creating(function (User $user): void {
            app(StableUserIdManager::class)->ensureAssigned($user);
        });

        User::created(function (User $user): void {
            $user->notificationPreference()->firstOrCreate();
        });

        Comment::created(function (Comment $comment): void {
            if ($comment->root_id === null) {
                $comment->forceFill([
                    'root_id' => $comment->parent?->root_id ?: $comment->parent_id ?: $comment->id,
                ])->saveQuietly();
            }

            $comment->subscriptions()->firstOrCreate(
                ['user_id' => $comment->user_id],
                ['is_active' => true],
            );
        });

        Gate::define('access-admin', fn (User $user) => $user->isAdmin());

        View::composer(['layouts.app', 'layouts.auth'], function ($view): void {
            $data = array_merge(
                app(CommunityViewData::class)->layout(),
                $view->getData(),
            );

            if (($data['seo'] ?? null) === null) {
                $data['seo'] = app(SeoMetadataFactory::class)->forCurrentRequest($data, $view->getName());
            }

            $view->with($data);
        });
    }
}
