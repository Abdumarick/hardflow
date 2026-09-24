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
        Schema::create('stock_balances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id');
            $t->foreignId('branch_id');
            $t->foreignId('product_id');
            $t->string('stock_status', 30);
            $t->decimal('quantity', 18, 4)->default(0);
            $t->decimal('average_cost', 18, 2)->default(0);
            $t->timestamps();
            $t->unique(['branch_id', 'product_id', 'stock_status']);
            $t->unique(['business_id', 'id']);
            $t->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $t->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
        });
        Schema::create('stock_adjustments', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('business_id');
            $t->foreignId('branch_id');
            $t->string('status', 20)->index();
            $t->text('reason');
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('decision_reason')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->unique(['business_id', 'id']);
            $t->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
        });
        Schema::create('stock_adjustment_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id');
            $t->foreignId('adjustment_id');
            $t->foreignId('product_id');
            $t->string('stock_status', 30);
            $t->decimal('system_quantity', 18, 4);
            $t->decimal('physical_quantity', 18, 4);
            $t->decimal('difference_quantity', 18, 4);
            $t->timestamps();
            $t->unique(['adjustment_id', 'product_id', 'stock_status'], 'sai_adjustment_product_status_uq');
            $t->foreign(['business_id', 'adjustment_id'])->references(['business_id', 'id'])->on('stock_adjustments')->restrictOnDelete();
            $t->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('business_id');
            $t->foreignId('branch_id');
            $t->foreignId('product_id');
            $t->string('stock_status', 30);
            $t->string('movement_type', 40)->index();
            $t->decimal('quantity_delta', 18, 4);
            $t->decimal('balance_before', 18, 4);
            $t->decimal('balance_after', 18, 4);
            $t->decimal('unit_cost', 18, 2)->nullable();
            $t->nullableMorphs('source');
            $t->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $t->text('reason')->nullable();
            $t->timestamp('occurred_at')->useCurrent();
            $t->timestamps();
            $t->index(['business_id', 'branch_id', 'product_id', 'occurred_at'], 'stock_movements_lookup');
            $t->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $t->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
        });
        Schema::create('stock_counts', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('business_id');
            $t->foreignId('branch_id');
            $t->string('status', 20)->default('draft');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('counted_at')->nullable();
            $t->timestamps();
            $t->unique(['business_id', 'id']);
            $t->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
        });
        Schema::create('stock_count_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id');
            $t->foreignId('stock_count_id');
            $t->foreignId('product_id');
            $t->string('stock_status', 30);
            $t->decimal('system_quantity', 18, 4);
            $t->decimal('physical_quantity', 18, 4)->nullable();
            $t->timestamps();
            $t->unique(['stock_count_id', 'product_id', 'stock_status'], 'sci_count_product_status_uq');
            $t->foreign(['business_id', 'stock_count_id'])->references(['business_id', 'id'])->on('stock_counts')->restrictOnDelete();
            $t->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
        });
        foreach ([PermissionName::InventoryView, PermissionName::InventoryReceive, PermissionName::InventoryOpening, PermissionName::InventoryAdjustRequest, PermissionName::InventoryAdjustApprove] as $p) {
            DB::table('permissions')->updateOrInsert(['slug' => $p->value], ['name' => $p->label(), 'module' => $p->module(), 'created_at' => now(), 'updated_at' => now()]);
        } $ids = DB::table('permissions')->where('module', 'inventory')->pluck('id');
        foreach (DB::table('roles')->where('slug', 'owner')->pluck('id') as $role) {
            foreach ($ids as $id) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_balances');
        DB::table('permissions')->where('module', 'inventory')->delete();
    }
};
