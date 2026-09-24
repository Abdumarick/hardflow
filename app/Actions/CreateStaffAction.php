<?php

namespace App\Actions;

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

class CreateStaffAction
{
    /**
     * @param  array{name:string, username?:string, email:string, phone?:string, password:string, branch_ids:list<int>, role_ids:list<int>}  $attributes
     */
    public function execute(User $actor, Business $business, array $attributes): User
    {
        Gate::forUser($actor)->authorize('createStaff', $business);

        if ($attributes['branch_ids'] === []) {
            throw ValidationException::withMessages(['branch_ids' => 'Select at least one branch.']);
        }

        if ($attributes['role_ids'] === []) {
            throw ValidationException::withMessages(['role_ids' => 'Select at least one role.']);
        }

        $branches = Branch::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->whereIn('id', $attributes['branch_ids'])
            ->get();
        $roles = Role::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->whereIn('id', $attributes['role_ids'])
            ->get();

        if ($branches->count() !== count(array_unique($attributes['branch_ids']))) {
            throw ValidationException::withMessages(['branch_ids' => 'One or more branches are invalid.']);
        }

        if ($roles->count() !== count(array_unique($attributes['role_ids']))) {
            throw ValidationException::withMessages(['role_ids' => 'One or more roles are invalid.']);
        }

        if (User::query()->where('email', $attributes['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'A user with this email already exists.']);
        }

        return DB::transaction(function () use ($actor, $business, $attributes, $branches, $roles): User {
            $user = User::query()->create([
                'name' => $attributes['name'],
                'username' => $attributes['username'] ?? null,
                'email' => $attributes['email'],
                'phone' => $attributes['phone'] ?? null,
                'password' => $attributes['password'],
            ]);

            BusinessUser::query()->create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'joined_at' => now(),
            ]);

            foreach ($branches as $branch) {
                BranchUser::query()->create([
                    'business_id' => $business->id,
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                ]);
            }

            foreach ($roles as $role) {
                UserRole::query()->create([
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $business->id,
                'action' => 'staff.created',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'new_values' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'branch_ids' => $branches->modelKeys(),
                    'role_ids' => $roles->modelKeys(),
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $user->load('memberships', 'branches', 'roles');
        });
    }
}
