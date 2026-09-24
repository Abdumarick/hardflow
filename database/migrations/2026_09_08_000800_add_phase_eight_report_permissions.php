<?php

use App\Enums\PermissionName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([PermissionName::ReportsView, PermissionName::ReportsProfit] as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission->value], ['name' => $permission->label(), 'module' => $permission->module(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $ids = DB::table('permissions')->whereIn('slug', [PermissionName::ReportsView->value, PermissionName::ReportsProfit->value])->pluck('id');
        foreach (DB::table('roles')->where('slug', 'owner')->pluck('id') as $roleId) {
            foreach ($ids as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('slug', [PermissionName::ReportsView->value, PermissionName::ReportsProfit->value])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
