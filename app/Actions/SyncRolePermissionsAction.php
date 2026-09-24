<?php

namespace App\Actions;

use App\Enums\DefaultRole;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SyncRolePermissionsAction
{
    /**
     * @param  list<int>  $permissionIds
     */
    public function execute(User $actor, Business $business, Role $role, array $permissionIds): Role
    {
        Gate::forUser($actor)->authorize('manageRoles', $business);

        if ($role->business_id !== $business->id) {
            throw new AuthorizationException('The role does not belong to this business.');
        }

        if ($role->is_system && $role->slug === DefaultRole::Owner->value) {
            throw ValidationException::withMessages([
                'role' => 'The Shop Owner role permissions cannot be reduced.',
            ]);
        }

        $permissions = Permission::query()->whereIn('id', $permissionIds)->get();
        if ($permissions->count() !== count(array_unique($permissionIds))) {
            throw ValidationException::withMessages(['permission_ids' => 'One or more permissions are invalid.']);
        }

        return DB::transaction(function () use ($actor, $business, $role, $permissions): Role {
            $oldPermissionIds = $role->permissions()->pluck('permissions.id')->all();
            $role->permissions()->sync($permissions->modelKeys());

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $business->id,
                'action' => 'role.permissions_updated',
                'subject_type' => Role::class,
                'subject_id' => $role->id,
                'old_values' => ['permission_ids' => $oldPermissionIds],
                'new_values' => ['permission_ids' => $permissions->modelKeys()],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $role->load('permissions');
        });
    }
}
