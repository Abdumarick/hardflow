<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\BranchUser;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EnableStaffAction
{
    public function execute(User $actor, Business $business, User $staff, string $reason): void
    {
        Gate::forUser($actor)->authorize('disableStaff', $business);

        DB::transaction(function () use ($actor, $business, $staff, $reason): void {
            $membership = BusinessUser::query()
                ->where('business_id', $business->id)
                ->where('user_id', $staff->id)
                ->lockForUpdate()
                ->firstOrFail();

            $membership->update(['is_active' => true, 'disabled_at' => null]);

            BranchUser::query()
                ->where('business_id', $business->id)
                ->where('user_id', $staff->id)
                ->whereHas('branch', fn ($query) => $query->where('is_active', true))
                ->update(['is_active' => true]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $business->id,
                'action' => 'staff.enabled',
                'subject_type' => User::class,
                'subject_id' => $staff->id,
                'old_values' => ['is_active' => false],
                'new_values' => ['is_active' => true],
                'reason' => $reason,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });
    }
}
