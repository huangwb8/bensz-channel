<?php

namespace App\Providers;

use App\Models\User;
use App\Support\CommunityViewData;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user) => $user->isAdmin());

        View::composer('*', function ($view): void {
            $view->with(array_merge(
                app(CommunityViewData::class)->chrome(),
                $view->getData(),
            ));
        });
    }
}
