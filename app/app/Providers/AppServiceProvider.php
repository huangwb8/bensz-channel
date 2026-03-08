<?php

namespace App\Providers;

use App\Contracts\Auth\OtpAuthGateway;
use App\Models\User;
use App\Services\Auth\BetterAuthGateway;
use App\Services\Auth\LegacyOtpGateway;
use App\Support\CommunityViewData;
use Illuminate\Support\Facades\Gate;
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
        User::created(function (User $user): void {
            $user->notificationPreference()->firstOrCreate();
        });

        Gate::define('access-admin', fn (User $user) => $user->isAdmin());

        View::composer(['layouts.app', 'layouts.auth'], function ($view): void {
            $view->with(array_merge(
                app(CommunityViewData::class)->layout(),
                $view->getData(),
            ));
        });
    }
}
