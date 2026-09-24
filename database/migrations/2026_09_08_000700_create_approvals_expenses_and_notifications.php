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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id')->nullable();
            $table->morphs('subject');
            $table->string('action_type', 80)->index();
            $table->string('status', 30)->default('pending')->index();
            $table->decimal('amount', 18, 2)->nullable();
            $table->text('request_reason');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();
            $table->unique(['subject_type', 'subject_id', 'action_type', 'status'], 'approval_subject_status_uq');
            $table->foreign('business_id')->references('id')->on('businesses')->restrictOnDelete();
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
        });
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'name']);
            $table->foreign('business_id')->references('id')->on('businesses')->restrictOnDelete();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('expense_category_id');
            $table->foreignId('payment_account_id')->nullable();
            $table->string('expense_number');
            $table->string('status', 20)->default('draft')->index();
            $table->string('title');
            $table->string('vendor')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 2);
            $table->date('expense_date');
            $table->string('receipt_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id']);
            $table->unique(['business_id', 'expense_number']);
            $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete();
            $table->foreign(['business_id', 'expense_category_id'])->references(['business_id', 'id'])->on('expense_categories')->restrictOnDelete();
            $table->foreign(['business_id', 'payment_account_id'])->references(['business_id', 'id'])->on('payment_accounts')->restrictOnDelete();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        foreach (array_filter(PermissionName::cases(), fn (PermissionName $permission) => in_array($permission->module(), ['expenses', 'approvals', 'notifications', 'audit'], true)) as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission->value], ['name' => $permission->label(), 'module' => $permission->module(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $ids = DB::table('permissions')->whereIn('module', ['expenses', 'approvals', 'notifications', 'audit'])->pluck('id');
        foreach (DB::table('roles')->where('slug', 'owner')->pluck('id') as $roleId) {
            foreach ($ids as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach (DB::table('businesses')->pluck('id') as $businessId) {
            foreach (['Office Supplies', 'Transport', 'Utilities', 'Equipment Maintenance', 'Security', 'Marketing', 'Rent & Lease'] as $name) {
                DB::table('expense_categories')->insert(['business_id' => $businessId, 'name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('approval_requests');
        $permissionIds = DB::table('permissions')->whereIn('module', ['expenses', 'approvals', 'notifications'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
