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
        Gate::define('manage-hr', function (User $user) {
            return in_array($user->role, ['hr', 'administrator']);
        });

        Gate::define('manage-team', function (User $user) {
            return in_array($user->role, ['team_lead', 'hr', 'administrator', 'manager']);
        });

        Gate::define('manage-analytics', function (User $user) {
            return in_array($user->role, ['hr', 'administrator', 'manager']);
        });

        Gate::define('apply-leave', function (User $user) {
            return !$user->isManager() && !$user->isSuperAdmin() && $user->employee()->exists();
        });
    }
}
