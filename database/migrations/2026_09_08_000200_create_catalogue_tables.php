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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->uuid('public_id')->unique();
            $table->foreignId('parent_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'parent_id', 'name']);
            $table->foreign(['business_id', 'parent_id'])->references(['business_id', 'id'])->on('categories')->restrictOnDelete();
        });
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['business_id', 'name']);
            $table->unique(['business_id', 'id']);
        });
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('symbol', 20);
            $table->boolean('allows_decimal')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['business_id', 'name']);
            $table->unique(['business_id', 'symbol']);
            $table->unique(['business_id', 'id']);
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->uuid('public_id')->unique();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('brand_id')->nullable();
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_stock_tracked')->default(true);
            $table->boolean('tracks_expiry')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['business_id', 'sku']);
            $table->unique(['business_id', 'barcode']);
            $table->unique(['business_id', 'id']);
            $table->index(['business_id', 'name']);
            $table->foreign(['business_id', 'category_id'])->references(['business_id', 'id'])->on('categories')->restrictOnDelete();
            $table->foreign(['business_id', 'brand_id'])->references(['business_id', 'id'])->on('brands')->restrictOnDelete();
        });
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('product_id');
            $table->foreignId('unit_id');
            $table->decimal('conversion_factor', 18, 6)->default(1);
            $table->boolean('is_base')->default(false);
            $table->boolean('can_purchase')->default(true);
            $table->boolean('can_sell')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'unit_id']);
            $table->unique(['business_id', 'id']);
            $table->index(['product_id', 'is_base']);
            $table->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
            $table->foreign(['business_id', 'unit_id'])->references(['business_id', 'id'])->on('units')->restrictOnDelete();
        });
        Schema::create('price_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('code', 30);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['business_id', 'name']);
            $table->unique(['business_id', 'code']);
            $table->unique(['business_id', 'id']);
        });
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('product_unit_id');
            $table->foreignId('price_level_id');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('TZS');
            $table->timestamp('effective_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['business_id', 'product_unit_id', 'price_level_id', 'is_active'], 'product_prices_lookup_index');
            $table->foreign(['business_id', 'product_unit_id'])->references(['business_id', 'id'])->on('product_units')->restrictOnDelete();
            $table->foreign(['business_id', 'price_level_id'])->references(['business_id', 'id'])->on('price_levels')->restrictOnDelete();
        });
        Schema::create('product_branch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('product_id');
            $table->decimal('minimum_stock', 18, 4)->default(0);
            $table->timestamps();
            $table->unique(['branch_id', 'product_id']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'product_id'])->references(['business_id', 'id'])->on('products')->restrictOnDelete();
        });

        foreach ([PermissionName::ProductsView, PermissionName::ProductsCreate, PermissionName::ProductsUpdate, PermissionName::ProductsChangePrice] as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission->value], ['name' => $permission->label(), 'module' => $permission->module(), 'updated_at' => now(), 'created_at' => now()]);
        }
        $ownerRoleIds = DB::table('roles')->where('slug', 'owner')->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', array_map(fn ($p) => $p->value, [PermissionName::ProductsView, PermissionName::ProductsCreate, PermissionName::ProductsUpdate, PermissionName::ProductsChangePrice]))->pluck('id');
        foreach ($ownerRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_branch_settings');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('price_levels');
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('products');
        Schema::dropIfExists('units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
        DB::table('permissions')->whereIn('slug', ['products.view', 'products.create', 'products.update', 'products.change_price'])->delete();
    }
};
