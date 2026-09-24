<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DisableUserAction
{
    public function execute(User $actor, User $user, string $reason): void
    {
        Gate::forUser($actor)->authorize('platform.manage-users');

        if ($actor->is($user)) {
            throw ValidationException::withMessages(['user' => 'You cannot disable your own platform account.']);
        }

        DB::transaction(function () use ($actor, $user, $reason): void {
            $user->update(['is_active' => false, 'disabled_at' => now()]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'user.disabled',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'old_values' => ['is_active' => true],
                'new_values' => ['is_active' => false],
                'reason' => $reason,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });
    }
}
