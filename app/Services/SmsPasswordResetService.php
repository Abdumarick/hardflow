<?php

namespace App\Services;

use App\Models\User;
use App\Support\TemporaryCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SmsPasswordResetService
{
    /**
     * Sends a temporary password and only replaces the current password after
     * the SMS provider accepts the message. Requests are rate-limited by both
     * account and source IP rather than permanently locking the account.
     */
    public function request(User $user): bool
    {
        if (blank($user->phone) || RateLimiter::tooManyAttempts($this->throttleKey($user), 3)) {
            return false;
        }

        $temporaryPassword = TemporaryCredentials::password($user->name);
        RateLimiter::hit($this->throttleKey($user), 60);

        if (! app(SewmrSmsService::class)->sendPasswordReset($user, $temporaryPassword)) {
            return false;
        }

        DB::transaction(function () use ($user, $temporaryPassword): void {
            $user->forceFill([
                'password' => Hash::make($temporaryPassword),
                'remember_token' => Str::random(60),
                'password_reset_requests' => 0,
                'password_reset_blocked_at' => null,
            ])->save();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });

        return true;
    }

    private function throttleKey(User $user): string
    {
        return 'password-reset:'.$user->id.'|'.(request()?->ip() ?? 'console');
    }
}
