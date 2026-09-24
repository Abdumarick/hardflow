<?php

namespace App\Actions;

use App\Enums\DefaultRole;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateStaffDetailsAction
{
    /** @param array{name:string,username:string,email:string,phone:string} $attributes */
    public function execute(User $actor, Business $business, User $staff, array $attributes, string $reason): void
    {
        Gate::forUser($actor)->authorize('updateStaff', $business);
        $this->validateTarget($actor, $business, $staff);

        DB::transaction(function () use ($actor, $attributes, $business, $reason, $staff): void {
            $old = $staff->only(['name', 'username', 'email', 'phone']);
            $staff->forceFill($attributes)->save();

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $business->id,
                'action' => 'staff.details_updated',
                'subject_type' => User::class,
                'subject_id' => $staff->id,
                'old_values' => $old,
                'new_values' => $staff->only(['name', 'username', 'email', 'phone']),
                'reason' => trim($reason),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });
    }

    private function validateTarget(User $actor, Business $business, User $staff): void
    {
        if ($actor->is($staff)) {
            throw ValidationException::withMessages(['staff' => 'Use your profile to update your own details.']);
        }
        if (! BusinessUser::query()->where('business_id', $business->id)->where('user_id', $staff->id)->exists()) {
            throw ValidationException::withMessages(['staff' => 'The selected user does not belong to this business.']);
        }
        $targetIsOwner = $staff->roles()->wherePivot('business_id', $business->id)->where('roles.slug', DefaultRole::Owner->value)->exists();
        if ($targetIsOwner && ! $actor->is_super_admin) {
            throw ValidationException::withMessages(['staff' => 'Only a Super Admin can manage a Shop Owner account.']);
        }
    }
}
