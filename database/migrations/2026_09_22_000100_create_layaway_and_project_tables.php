<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL leaves newly-created tables behind when a later foreign key in this migration fails.
        // These tables are introduced together, so clear only an interrupted first-run attempt.
        Schema::dropIfExists('project_payment_requests');
        Schema::dropIfExists('project_contracts');
        Schema::dropIfExists('layaway_payments');
        Schema::dropIfExists('layaway_plans');
        Schema::create('layaway_plans', function (Blueprint $table) {
            $table->id(); $table->uuid('public_id')->unique(); $table->foreignId('business_id'); $table->foreignId('branch_id'); $table->foreignId('customer_id');
            $table->string('plan_number'); $table->decimal('target_amount', 18, 2); $table->decimal('paid_amount', 18, 2)->default(0); $table->date('collection_due_date')->nullable();
            $table->string('status', 20)->default('active')->index(); $table->text('notes')->nullable(); $table->foreignId('created_by')->constrained('users')->restrictOnDelete(); $table->timestamp('completed_at')->nullable(); $table->timestamps();
            $table->unique(['business_id', 'id']); $table->unique(['business_id', 'plan_number']); $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete(); $table->foreign(['business_id', 'customer_id'])->references(['business_id', 'id'])->on('customers')->restrictOnDelete();
        });
        Schema::create('layaway_payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('business_id'); $table->foreignId('layaway_plan_id'); $table->foreignId('payment_id')->unique(); $table->decimal('amount', 18, 2); $table->timestamps();
            $table->foreign(['business_id', 'layaway_plan_id'])->references(['business_id', 'id'])->on('layaway_plans')->restrictOnDelete(); $table->foreign(['business_id', 'payment_id'])->references(['business_id', 'id'])->on('payments')->restrictOnDelete();
        });
        Schema::create('project_contracts', function (Blueprint $table) {
            $table->id(); $table->uuid('public_id')->unique(); $table->foreignId('business_id'); $table->foreignId('branch_id'); $table->foreignId('customer_id');
            $table->string('project_number'); $table->string('name'); $table->string('location')->nullable(); $table->decimal('contract_amount', 18, 2); $table->decimal('paid_amount', 18, 2)->default(0); $table->string('payment_mode', 20); $table->string('status', 40)->default('active')->index(); $table->date('start_date')->nullable(); $table->date('completion_date')->nullable(); $table->text('notes')->nullable(); $table->foreignId('created_by')->constrained('users')->restrictOnDelete(); $table->timestamps();
            $table->unique(['business_id', 'id']); $table->unique(['business_id', 'project_number']); $table->foreign(['business_id', 'branch_id'])->references(['business_id', 'id'])->on('branches')->restrictOnDelete(); $table->foreign(['business_id', 'customer_id'])->references(['business_id', 'id'])->on('customers')->restrictOnDelete();
        });
        Schema::create('project_payment_requests', function (Blueprint $table) {
            $table->id(); $table->foreignId('business_id'); $table->foreignId('project_contract_id'); $table->string('request_number'); $table->string('title'); $table->decimal('amount', 18, 2); $table->date('requested_date'); $table->string('status', 20)->default('requested'); $table->text('notes')->nullable(); $table->timestamps();
            $table->unique(['business_id', 'request_number']); $table->foreign(['business_id', 'project_contract_id'])->references(['business_id', 'id'])->on('project_contracts')->restrictOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('project_payment_requests'); Schema::dropIfExists('project_contracts'); Schema::dropIfExists('layaway_payments'); Schema::dropIfExists('layaway_plans'); }
};
