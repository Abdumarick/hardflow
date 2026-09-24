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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('tin', 50)->nullable();
            $table->text('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'name']);
            $table->unique(['business_id', 'id']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('supplier_id')->nullable();
            $table->string('purchase_number');
            $table->string('supplier_reference')->nullable();
            $table->string('status', 25)->default('draft')->index();
            $table->date('purchase_date');
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'purchase_number']);
            $table->unique(['business_id', 'id']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'supplier_id'])->references(['business_id', 'id'])->on('suppliers')->restrictOnDelete();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('purchase_id');
            $table->foreignId('product_id');
            $table->foreignId('product_unit_id');
            $table->decimal('ordered_quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 2);
            $table->decimal('conversion_factor', 18, 6);
            $table->decimal('line_total', 18, 2);
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['purchase_id', 'product_id', 'product_unit_id'], 'pi_purchase_product_unit_uq');
            $table->foreign(['business_id', 'purchase_id'])->references(['business_id', 'id'])->on('purchases')->restrictOnDelete();
            $table->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
            $table->foreign(['business_id', 'product_unit_id'])->references(['business_id', 'id'])->on('product_units')->restrictOnDelete();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('purchase_id');
            $table->string('receipt_number');
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'receipt_number']);
            $table->unique(['business_id', 'id']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'purchase_id'])->references(['business_id', 'id'])->on('purchases')->restrictOnDelete();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('goods_receipt_id');
            $table->foreignId('purchase_item_id');
            $table->decimal('received_quantity', 18, 4);
            $table->decimal('accepted_quantity', 18, 4);
            $table->decimal('damaged_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 2);
            $table->text('difference_reason')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['goods_receipt_id', 'purchase_item_id'], 'gri_receipt_purchase_item_uq');
            $table->foreign(['business_id', 'goods_receipt_id'])->references(['business_id', 'id'])->on('goods_receipts')->restrictOnDelete();
            $table->foreign(['business_id', 'purchase_item_id'])->references(['business_id', 'id'])->on('purchase_items')->restrictOnDelete();
        });

        Schema::create('product_cost_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('product_id');
            $table->foreignId('goods_receipt_item_id');
            $table->decimal('supplied_unit_cost', 18, 2);
            $table->decimal('conversion_factor', 18, 6);
            $table->decimal('base_unit_cost', 18, 6);
            $table->decimal('previous_average_cost', 18, 2);
            $table->decimal('new_average_cost', 18, 2);
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->unique('goods_receipt_item_id');
            $table->foreign(['business_id', 'goods_receipt_item_id'])->references(['business_id', 'id'])->on('goods_receipt_items')->restrictOnDelete();
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
        });

        foreach ([
            PermissionName::PurchasesView, PermissionName::PurchasesCreate, PermissionName::PurchasesUpdate,
            PermissionName::PurchasesOrder, PermissionName::PurchasesReceive, PermissionName::PurchasesConfirmReceipt,
            PermissionName::SuppliersView, PermissionName::SuppliersCreate, PermissionName::SuppliersUpdate,
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission->value],
                ['name' => $permission->label(), 'module' => $permission->module(), 'created_at' => now(), 'updated_at' => now()],
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('module', ['purchases', 'suppliers'])->pluck('id');
        foreach (DB::table('roles')->where('slug', 'owner')->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_cost_history');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        DB::table('permissions')->whereIn('module', ['purchases', 'suppliers'])->delete();
    }
};
