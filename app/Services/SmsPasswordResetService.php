<?php

namespace App\Services;

use App\Models\User;
use App\Support\TemporaryCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SmsPasswordResetService
{
    /**
     * Creates and sends a temporary password. A fourth self-service request is
     * blocked until a business owner or Super Admin resets the account.
     */
    public function request(User $user): bool
    {
        if (blank($user->phone) || $user->password_reset_blocked_at || $user->password_reset_requests >= 3) {
            if (! $user->password_reset_blocked_at && $user->password_reset_requests >= 3) {
                $user->forceFill(['password_reset_blocked_at' => now()])->save();
            }

            return false;
        }

        $temporaryPassword = TemporaryCredentials::password($user->name);

        DB::transaction(function () use ($user, $temporaryPassword): void {
            $user->forceFill([
                'password' => Hash::make($temporaryPassword),
                'remember_token' => Str::random(60),
                'password_reset_requests' => $user->password_reset_requests + 1,
            ])->save();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });

        app(SewmrSmsService::class)->sendPasswordReset($user->fresh(), $temporaryPassword);

        return true;
    }
}
