<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DisableBusinessAction
{
    public function execute(User $actor, Business $business, string $reason): void
    {
        Gate::forUser($actor)->authorize('disable', $business);

        DB::transaction(function () use ($actor, $business, $reason): void {
            $business->update(['is_active' => false, 'disabled_at' => now()]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'business_id' => $business->id,
                'action' => 'business.disabled',
                'subject_type' => Business::class,
                'subject_id' => $business->id,
                'old_values' => ['is_active' => true],
                'new_values' => ['is_active' => false],
                'reason' => $reason,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });
    }
}
