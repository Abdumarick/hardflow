<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProvisionSuperAdminAction
{
    public function execute(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password): User {
            $user = User::query()->firstOrNew(['email' => $email]);
            $wasExisting = $user->exists;

            $user->fill([
                'name' => $name,
                'password' => $password,
                'is_super_admin' => true,
                'is_active' => true,
                'disabled_at' => null,
            ])->save();

            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => $wasExisting ? 'platform.super_admin_promoted' : 'platform.super_admin_created',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'new_values' => ['email' => $user->email, 'is_super_admin' => true],
            ]);

            return $user;
        });
    }
}
