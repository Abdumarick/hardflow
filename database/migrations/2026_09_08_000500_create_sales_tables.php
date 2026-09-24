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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('location')->nullable();
            $table->boolean('is_credit_customer')->default(false);
            $table->decimal('credit_limit', 18, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->index(['business_id', 'name']);
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('customer_id')->nullable();
            $table->string('quotation_number');
            $table->string('document_type', 20)->default('quotation');
            $table->string('status', 20)->default('draft')->index();
            $table->string('walk_in_name')->nullable();
            $table->string('walk_in_phone', 30)->nullable();
            $table->text('walk_in_location')->nullable();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('converted_sale_id')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'quotation_number']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'customer_id'])->references(['business_id', 'id'])->on('customers')->restrictOnDelete();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('quotation_id');
            $table->foreignId('product_id');
            $table->foreignId('product_unit_id');
            $table->decimal('quantity', 18, 4);
            $table->decimal('conversion_factor', 18, 6);
            $table->decimal('original_unit_price', 18, 2);
            $table->decimal('applied_unit_price', 18, 2);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2);
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->foreign(['business_id', 'quotation_id'])->references(['business_id', 'id'])->on('quotations')->restrictOnDelete();
            $table->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
            $table->foreign(['business_id', 'product_unit_id'])->references(['business_id', 'id'])->on('product_units')->restrictOnDelete();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->string('sale_number');
            $table->string('status', 20)->default('draft')->index();
            $table->string('payment_status', 25)->default('unpaid')->index();
            $table->string('fulfillment_status', 30)->default('on_hold')->index();
            $table->string('walk_in_name')->nullable();
            $table->string('walk_in_phone', 30)->nullable();
            $table->date('sale_date');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'sale_number']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'customer_id'])->references(['business_id', 'id'])->on('customers')->restrictOnDelete();
            $table->foreign(['business_id', 'quotation_id'])->references(['business_id', 'id'])->on('quotations')->restrictOnDelete();
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->foreign(['business_id', 'converted_sale_id'])->references(['business_id', 'id'])->on('sales')->restrictOnDelete();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('sale_id');
            $table->foreignId('product_id');
            $table->foreignId('product_unit_id');
            $table->decimal('quantity', 18, 4);
            $table->decimal('released_quantity', 18, 4)->default(0);
            $table->decimal('conversion_factor', 18, 6);
            $table->decimal('original_unit_price', 18, 2);
            $table->decimal('applied_unit_price', 18, 2);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('cost_snapshot', 18, 2);
            $table->decimal('line_total', 18, 2);
            $table->foreignId('price_overridden_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('price_override_reason')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->foreign(['business_id', 'sale_id'])->references(['business_id', 'id'])->on('sales')->restrictOnDelete();
            $table->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
            $table->foreign(['business_id', 'product_unit_id'])->references(['business_id', 'id'])->on('product_units')->restrictOnDelete();
        });

        Schema::create('goods_releases', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('sale_id');
            $table->string('release_number');
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('released_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'release_number']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'sale_id'])->references(['business_id', 'id'])->on('sales')->restrictOnDelete();
        });

        Schema::create('goods_release_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('goods_release_id');
            $table->foreignId('sale_item_id');
            $table->decimal('quantity', 18, 4);
            $table->timestamps();
            $table->unique(['goods_release_id', 'sale_item_id'], 'grelease_item_uq');
            $table->foreign(['business_id', 'goods_release_id'])->references(['business_id', 'id'])->on('goods_releases')->restrictOnDelete();
            $table->foreign(['business_id', 'sale_item_id'])->references(['business_id', 'id'])->on('sale_items')->restrictOnDelete();
        });

        foreach (array_filter(PermissionName::cases(), fn (PermissionName $permission) => in_array($permission->module(), ['customers', 'quotations', 'sales'], true)) as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission->value], ['name' => $permission->label(), 'module' => $permission->module(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $ids = DB::table('permissions')->whereIn('module', ['customers', 'quotations', 'sales'])->pluck('id');
        foreach (DB::table('roles')->where('slug', 'owner')->pluck('id') as $roleId) {
            foreach ($ids as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_release_items');
        Schema::dropIfExists('goods_releases');
        Schema::dropIfExists('sale_items');
        Schema::table('quotations', fn (Blueprint $table) => $table->dropForeign(['business_id', 'converted_sale_id']));
        Schema::dropIfExists('sales');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('customers');
        DB::table('permissions')->whereIn('module', ['customers', 'quotations', 'sales'])->delete();
    }
};
