<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code', 20)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tin', 50)->nullable();
            $table->string('vrn', 50)->nullable();
            $table->string('currency', 3)->default('TZS');
            $table->string('timezone')->default('Africa/Dar_es_Salaam');
            $table->string('locale', 10)->default('en');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('code', 20);
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->unique(['business_id', 'id']);
        });

        Schema::create('business_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
        });

        Schema::create('branch_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->foreignId('user_id');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['branch_id', 'user_id']);
            $table->foreign(['business_id', 'branch_id'])
                ->references(['business_id', 'id'])
                ->on('branches')
                ->restrictOnDelete();
            $table->foreign(['business_id', 'user_id'])
                ->references(['business_id', 'user_id'])
                ->on('business_users')
                ->restrictOnDelete();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module', 50)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['business_id', 'slug']);
            $table->unique(['business_id', 'id']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->foreignId('permission_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();

            $table->unique(['business_id', 'user_id', 'role_id']);
            $table->foreign(['business_id', 'user_id'])
                ->references(['business_id', 'user_id'])
                ->on('business_users')
                ->restrictOnDelete();
            $table->foreign(['business_id', 'role_id'])
                ->references(['business_id', 'id'])
                ->on('roles')
                ->restrictOnDelete();
        });

        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'key']);
        });

        Schema::create('branch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'key']);
            $table->foreign(['business_id', 'branch_id'])
                ->references(['business_id', 'id'])
                ->on('branches')
                ->restrictOnDelete();
        });

        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreignId('branch_id');
            $table->string('type', 30);
            $table->string('prefix', 30);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->timestamps();

            $table->unique(['branch_id', 'type']);
            $table->foreign(['business_id', 'branch_id'])
                ->references(['business_id', 'id'])
                ->on('branches')
                ->restrictOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action', 100)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['business_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('branch_settings');
        Schema::dropIfExists('business_settings');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('branch_users');
        Schema::dropIfExists('business_users');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('businesses');
    }
};
