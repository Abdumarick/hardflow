<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleIds = DB::table('roles')->where('slug', 'manager')->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', ['users.view', 'users.update'])->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')->where('slug', 'manager')->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', ['users.view', 'users.update'])->pluck('id');
        DB::table('role_permissions')->whereIn('role_id', $roleIds)->whereIn('permission_id', $permissionIds)->delete();
    }
};
