<?php

namespace App\Actions;

use App\Enums\DefaultRole;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchUser;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateStaffAccessAction
{
    /** @param list<int> $branchIds @param list<int> $roleIds */
    public function execute(User $actor, Business $business, User $staff, array $branchIds, array $roleIds, string $reason): void
    {
        Gate::forUser($actor)->authorize('updateStaff', $business);
        if ($actor->is($staff)) {
            throw ValidationException::withMessages(['staff' => 'You cannot change your own access.']);
        }
        if (! BusinessUser::query()->where('business_id', $business->id)->where('user_id', $staff->id)->exists()) {
            throw ValidationException::withMessages(['staff' => 'The selected user does not belong to this business.']);
        }
        $branches = Branch::query()->where('business_id', $business->id)->where('is_active', true)->whereIn('id', $branchIds)->get();
        $roles = Role::query()->where('business_id', $business->id)->where('is_active', true)->whereIn('id', $roleIds)->get();
        if ($branches->count() !== count(array_unique($branchIds)) || $roles->count() !== count(array_unique($roleIds))) {
            throw ValidationException::withMessages(['access' => 'One or more selected branches or roles are invalid.']);
        }
        $targetIsOwner = $staff->roles()->wherePivot('business_id', $business->id)->where('roles.slug', DefaultRole::Owner->value)->exists();
        if (($targetIsOwner || $roles->contains('slug', DefaultRole::Owner->value)) && ! $actor->is_super_admin) {
            throw ValidationException::withMessages(['role_ids' => 'Only a Super Admin can manage or assign the Shop Owner role.']);
        }

        DB::transaction(function () use ($actor, $business, $staff, $branches, $roles, $reason): void {
            $old = ['branch_ids' => BranchUser::query()->where('business_id', $business->id)->where('user_id', $staff->id)->where('is_active', true)->pluck('branch_id')->all(), 'role_ids' => UserRole::query()->where('business_id', $business->id)->where('user_id', $staff->id)->pluck('role_id')->all()];
            BranchUser::query()->where('business_id', $business->id)->where('user_id', $staff->id)->update(['is_active' => false]);
            foreach ($branches as $branch) {
                BranchUser::query()->updateOrCreate(['business_id' => $business->id, 'branch_id' => $branch->id, 'user_id' => $staff->id], ['is_active' => true]);
            }
            UserRole::query()->where('business_id', $business->id)->where('user_id', $staff->id)->delete();
            foreach ($roles as $role) {
                UserRole::query()->create(['business_id' => $business->id, 'user_id' => $staff->id, 'role_id' => $role->id]);
            }
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $business->id, 'action' => 'staff.access_updated', 'subject_type' => User::class, 'subject_id' => $staff->id, 'old_values' => $old, 'new_values' => ['branch_ids' => $branches->modelKeys(), 'role_ids' => $roles->modelKeys()], 'reason' => trim($reason), 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent()]);
        });
    }
}
