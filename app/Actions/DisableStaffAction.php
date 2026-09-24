<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\BranchUser;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DisableStaffAction
{
    public function execute(User $actor, Business $business, User $staff, string $reason): void
    {
        Gate::forUser($actor)->authorize('disableStaff', $business);

        if ($actor->is($staff)) {
            throw ValidationException::withMessages(['staff' => 'You cannot disable your own membership.']);
        }

        DB::transaction(function () use ($actor, $business, $staff, $reason): void {
            $membership = BusinessUser::query()
                ->where('business_id', $business->id)
                ->where('user_id', $staff->id)
                ->lockForUpdate()
                ->firstOrFail();

            $membership->update([
                'is_active' => false,
                'disabled_at' => now(),
            ]);

            BranchUser::query()
                ->where('business_id', $business->id)
                ->where('user_id', $staff->id)
                ->update(['is_active' => false]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $business->id,
                'action' => 'staff.disabled',
                'subject_type' => User::class,
                'subject_id' => $staff->id,
                'old_values' => ['is_active' => true],
                'new_values' => ['is_active' => false],
                'reason' => $reason,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });
    }
}
