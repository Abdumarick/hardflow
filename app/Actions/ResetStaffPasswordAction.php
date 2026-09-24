<?php

namespace App\Actions;

use App\Enums\DefaultRole;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetStaffPasswordAction
{
    public function execute(User $actor, Business $business, User $staff, string $password, string $reason): void
    {
        Gate::forUser($actor)->authorize('updateStaff', $business);
        $this->validateTarget($actor, $business, $staff);

        DB::transaction(function () use ($actor, $business, $staff, $password, $reason): void {
            $staff->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
                'password_reset_requests' => 0,
                'password_reset_blocked_at' => null,
            ])->save();
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $staff->id)->delete();
            }
            AuditLog::query()->create([
                'user_id' => $actor->id, 'business_id' => $business->id, 'action' => 'staff.password_reset',
                'subject_type' => User::class, 'subject_id' => $staff->id,
                'new_values' => ['sessions_revoked' => true], 'reason' => trim($reason),
                'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent(),
            ]);
        });
    }

    private function validateTarget(User $actor, Business $business, User $staff): void
    {
        if ($actor->is($staff)) {
            throw ValidationException::withMessages(['staff' => 'Use your profile to change your own password.']);
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
