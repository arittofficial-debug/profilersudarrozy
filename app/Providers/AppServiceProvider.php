<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\Gate::before(function (\App\Models\User $user) {
            return $user->hasRole('super_admin') ? true : null;
        });

        try {
            \App\Models\Permission::query()
                ->pluck('slug')
                ->each(function (string $slug) {
                    \Illuminate\Support\Facades\Gate::define($slug, fn (\App\Models\User $user) => $user->hasPermissionTo($slug));
                });
        } catch (\Throwable) {
            // Permissions table may not exist during migrations or tests.
        }
    }
}
