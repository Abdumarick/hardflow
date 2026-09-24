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
        Schema::create('supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('supplier_id');
            $table->foreignId('purchase_id')->nullable();
            $table->string('return_number');
            $table->string('status', 20)->default('pending')->index();
            $table->text('reason');
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('decision_reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'return_number']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'supplier_id'])->references(['business_id', 'id'])->on('suppliers')->restrictOnDelete();
            $table->foreign(['business_id', 'purchase_id'])->references(['business_id', 'id'])->on('purchases')->restrictOnDelete();
        });

        Schema::create('supplier_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('supplier_return_id');
            $table->foreignId('purchase_item_id');
            $table->foreignId('product_id');
            $table->string('stock_status', 30);
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 2);
            $table->decimal('line_total', 18, 2);
            $table->timestamps();
            $table->unique(['supplier_return_id', 'purchase_item_id', 'stock_status'], 'sri_return_item_status_uq');
            $table->foreign(['business_id', 'supplier_return_id'])->references(['business_id', 'id'])->on('supplier_returns')->restrictOnDelete();
            $table->foreign(['business_id', 'purchase_item_id'])->references(['business_id', 'id'])->on('purchase_items')->restrictOnDelete();
            $table->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
        });

        Schema::create('supplier_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('supplier_id');
            $table->string('entry_type', 30)->index();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->nullableMorphs('source');
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'supplier_id', 'occurred_at'], 'supplier_ledger_lookup');
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'supplier_id'])->references(['business_id', 'id'])->on('suppliers')->restrictOnDelete();
        });

        foreach ([PermissionName::PurchasesReturnRequest, PermissionName::PurchasesReturnApprove] as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission->value],
                ['name' => $permission->label(), 'module' => $permission->module(), 'created_at' => now(), 'updated_at' => now()],
            );
        }
        $permissionIds = DB::table('permissions')->whereIn('slug', [PermissionName::PurchasesReturnRequest->value, PermissionName::PurchasesReturnApprove->value])->pluck('id');
        foreach (DB::table('roles')->where('slug', 'owner')->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledger_entries');
        Schema::dropIfExists('supplier_return_items');
        Schema::dropIfExists('supplier_returns');
        DB::table('permissions')->whereIn('slug', [PermissionName::PurchasesReturnRequest->value, PermissionName::PurchasesReturnApprove->value])->delete();
    }
};
