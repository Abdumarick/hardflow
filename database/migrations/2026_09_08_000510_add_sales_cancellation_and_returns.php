<?php

use App\Enums\PermissionName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->after('confirmed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('confirmed_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('sale_id');
            $table->string('return_number');
            $table->string('status', 20)->default('pending')->index();
            $table->text('reason');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'return_number']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'sale_id'])->references(['business_id', 'id'])->on('sales')->restrictOnDelete();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('sale_return_id');
            $table->foreignId('sale_item_id');
            $table->string('stock_status', 30);
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_amount', 18, 2);
            $table->decimal('line_total', 18, 2);
            $table->timestamps();
            $table->unique(['sale_return_id', 'sale_item_id'], 'sale_return_item_uq');
            $table->foreign(['business_id', 'sale_return_id'])->references(['business_id', 'id'])->on('sale_returns')->restrictOnDelete();
            $table->foreign(['business_id', 'sale_item_id'])->references(['business_id', 'id'])->on('sale_items')->restrictOnDelete();
        });

        $permissions = [PermissionName::SalesReturnRequest, PermissionName::SalesReturnApprove];
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission->value], ['name' => $permission->label(), 'module' => 'sales', 'created_at' => now(), 'updated_at' => now()]);
        }
        $permissionIds = DB::table('permissions')->whereIn('slug', array_map(fn ($permission) => $permission->value, $permissions))->pluck('id');
        foreach (DB::table('roles')->where('slug', 'owner')->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
        DB::table('permissions')->whereIn('slug', [PermissionName::SalesReturnRequest->value, PermissionName::SalesReturnApprove->value])->delete();
    }
};
