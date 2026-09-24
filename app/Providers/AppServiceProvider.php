<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\BusinessPolicy;
use App\Policies\ProductPolicy;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::define('platform.manage-users', fn (User $user): bool => $user->is_active && $user->is_super_admin);
        Gate::define('platform.manage-businesses', fn (User $user): bool => $user->is_active && $user->is_super_admin);

        Gate::before(function (User $user): ?bool {
            return $user->is_active && $user->is_super_admin ? true : null;
        });
    }
}
