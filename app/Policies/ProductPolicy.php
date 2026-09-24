<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Product;
use App\Models\User;
use App\Support\TenantContext;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        $b = app(TenantContext::class)->business();

        return $b !== null && $user->hasPermissionInBusiness(PermissionName::ProductsView, $b);
    }

    public function view(User $user, Product $product): bool
    {
        return $product->business_id === app(TenantContext::class)->businessId() && $user->hasPermissionInBusiness(PermissionName::ProductsView, $product->business);
    }

    public function create(User $user): bool
    {
        $b = app(TenantContext::class)->business();

        return $b !== null && $user->hasPermissionInBusiness(PermissionName::ProductsCreate, $b);
    }

    public function update(User $user, Product $product): bool
    {
        return $product->business_id === app(TenantContext::class)->businessId() && $user->hasPermissionInBusiness(PermissionName::ProductsUpdate, $product->business);
    }

    public function changePrice(User $user, Product $product): bool
    {
        return $product->business_id === app(TenantContext::class)->businessId() && $user->hasPermissionInBusiness(PermissionName::ProductsChangePrice, $product->business);
    }
}
