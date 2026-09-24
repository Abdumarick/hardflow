<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateRoleAction
{
    /**
     * @param  list<int>  $permissionIds
     */
    public function execute(
        User $actor,
        Business $business,
        string $name,
        array $permissionIds,
        ?string $description = null,
    ): Role {
        Gate::forUser($actor)->authorize('manageRoles', $business);

        $permissions = Permission::query()->whereIn('id', $permissionIds)->get();
        if ($permissions->count() !== count(array_unique($permissionIds))) {
            throw ValidationException::withMessages(['permission_ids' => 'One or more permissions are invalid.']);
        }

        return DB::transaction(function () use ($actor, $business, $name, $description, $permissions): Role {
            $role = Role::query()->create([
                'business_id' => $business->id,
                'name' => $name,
                'slug' => $this->uniqueSlug($business, $name),
                'description' => $description,
            ]);

            $role->permissions()->sync($permissions->modelKeys());

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $business->id,
                'action' => 'role.created',
                'subject_type' => Role::class,
                'subject_id' => $role->id,
                'new_values' => [
                    'name' => $role->name,
                    'permission_ids' => $permissions->modelKeys(),
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $role->load('permissions');
        });
    }

    private function uniqueSlug(Business $business, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Role::query()->where('business_id', $business->id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
