<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_contracts', fn (Blueprint $table) => $table->string('status', 40)->default('active')->change());
    }

    public function down(): void
    {
        Schema::table('project_contracts', fn (Blueprint $table) => $table->string('status', 20)->default('active')->change());
    }
};
