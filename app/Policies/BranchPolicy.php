<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Branch;
use App\Models\User;
use App\Support\TenantContext;

class BranchPolicy
{
    public function view(User $user, Branch $branch): bool
    {
        return $branch->is_active
            && $branch->business->is_active
            && $user->hasActiveBranchAccess($branch);
    }

    public function create(User $user): bool
    {
        return app(TenantContext::class)->business() !== null
            && $user->hasPermissionInBusiness(
                PermissionName::BranchesCreate,
                app(TenantContext::class)->business(),
            );
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermissionInBusiness(PermissionName::BranchesUpdate, $branch->business);
    }
}
