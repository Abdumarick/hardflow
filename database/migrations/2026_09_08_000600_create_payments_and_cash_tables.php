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
        Schema::table('sales', fn (Blueprint $table) => $table->date('due_date')->nullable()->after('sale_date')->index());

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->string('name');
            $table->string('type', 30);
            $table->boolean('requires_reference')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'name']);
            $table->foreign('business_id')->references('id')->on('businesses')->restrictOnDelete();
        });
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('payment_method_id');
            $table->string('name');
            $table->string('account_reference')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'name']);
            $table->foreign(['business_id', 'payment_method_id'])->references(['business_id', 'id'])->on('payment_methods')->restrictOnDelete();
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('payment_account_id');
            $table->string('payment_number');
            $table->string('status', 20)->default('confirmed')->index();
            $table->date('payment_date');
            $table->decimal('amount', 18, 2);
            $table->string('external_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'payment_number']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'customer_id'])->references(['business_id', 'id'])->on('customers')->restrictOnDelete();
            $table->foreign(['business_id', 'payment_account_id'])->references(['business_id', 'id'])->on('payment_accounts')->restrictOnDelete();
        });
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('payment_id');
            $table->foreignId('sale_id');
            $table->decimal('amount', 18, 2);
            $table->timestamps();
            $table->unique(['payment_id', 'sale_id']);
            $table->foreign(['business_id', 'payment_id'])->references(['business_id', 'id'])->on('payments')->restrictOnDelete();
            $table->foreign(['business_id', 'sale_id'])->references(['business_id', 'id'])->on('sales')->restrictOnDelete();
        });
        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('customer_id');
            $table->string('entry_type', 30);
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->nullableMorphs('source');
            $table->string('reference');
            $table->text('description')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['source_type', 'source_id', 'entry_type'], 'customer_ledger_source_uq');
            $table->index(['customer_id', 'occurred_at']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'customer_id'])->references(['business_id', 'id'])->on('customers')->restrictOnDelete();
        });
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('from_account_id');
            $table->foreignId('to_account_id');
            $table->string('transfer_number');
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('amount', 18, 2);
            $table->text('reason');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'transfer_number']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'from_account_id'])->references(['business_id', 'id'])->on('payment_accounts')->restrictOnDelete();
            $table->foreign(['business_id', 'to_account_id'])->references(['business_id', 'id'])->on('payment_accounts')->restrictOnDelete();
        });
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('payment_account_id');
            $table->string('status', 20)->default('open')->index();
            $table->decimal('opening_cash', 18, 2);
            $table->decimal('expected_closing_cash', 18, 2)->nullable();
            $table->decimal('actual_closing_cash', 18, 2)->nullable();
            $table->decimal('difference_amount', 18, 2)->nullable();
            $table->text('difference_reason')->nullable();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->index(['payment_account_id', 'status']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'payment_account_id'])->references(['business_id', 'id'])->on('payment_accounts')->restrictOnDelete();
        });

        $permissions = array_filter(PermissionName::cases(), fn (PermissionName $permission) => $permission->module() === 'payments');
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission->value], ['name' => $permission->label(), 'module' => 'payments', 'created_at' => now(), 'updated_at' => now()]);
        }
        $ids = DB::table('permissions')->where('module', 'payments')->pluck('id');
        foreach (DB::table('roles')->where('slug', 'owner')->pluck('id') as $roleId) {
            foreach ($ids as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        foreach (DB::table('businesses')->pluck('id') as $businessId) {
            foreach ([['Cash', 'cash', false], ['Mobile Money', 'mobile_money', true], ['Bank Transfer', 'bank', true], ['Card', 'card', true]] as [$name, $type, $reference]) {
                DB::table('payment_methods')->insert(['business_id' => $businessId, 'name' => $name, 'type' => $type, 'requires_reference' => $reference, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
        Schema::dropIfExists('account_transfers');
        Schema::dropIfExists('customer_ledger_entries');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_accounts');
        Schema::dropIfExists('payment_methods');
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('due_date'));
        DB::table('permissions')->where('module', 'payments')->delete();
    }
};
