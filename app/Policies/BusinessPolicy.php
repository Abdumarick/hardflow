<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->is_super_admin;
    }

    public function view(User $user, Business $business): bool
    {
        return $user->is_active && $business->is_active && $user->hasActiveMembership($business);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->is_super_admin;
    }

    public function update(User $user, Business $business): bool
    {
        return $business->is_active && $user->hasPermissionInBusiness(PermissionName::BusinessesUpdate, $business);
    }

    public function viewStaff(User $user, Business $business): bool
    {
        return $user->hasPermissionInBusiness(PermissionName::UsersView, $business);
    }

    public function createStaff(User $user, Business $business): bool
    {
        return $user->hasPermissionInBusiness(PermissionName::UsersCreate, $business);
    }

    public function disableStaff(User $user, Business $business): bool
    {
        return $user->hasPermissionInBusiness(PermissionName::UsersDisable, $business);
    }

    public function updateStaff(User $user, Business $business): bool
    {
        return $user->hasPermissionInBusiness(PermissionName::UsersUpdate, $business);
    }

    public function manageRoles(User $user, Business $business): bool
    {
        return $user->hasPermissionInBusiness(PermissionName::UsersManageRoles, $business);
    }

    public function disable(User $user, Business $business): bool
    {
        return $user->is_active && $user->is_super_admin && $business->is_active;
    }
}
