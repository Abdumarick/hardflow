<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->unsignedTinyInteger('password_reset_requests')->default(0)->after('remember_token');
            $table->timestamp('password_reset_blocked_at')->nullable()->after('password_reset_requests');
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_phone_unique');
            $table->dropUnique('users_username_unique');
            $table->dropColumn(['username', 'password_reset_requests', 'password_reset_blocked_at']);
        });
    }
};
