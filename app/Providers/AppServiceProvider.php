<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::define('use-pos', fn (User $user): bool => $user->canAccessPos());
        Gate::define('manage-store', fn (User $user): bool => $user->isStoreAdmin());
        Gate::define('view-customer-orders', fn (User $user): bool => $user->isBuyer());
    }
}
