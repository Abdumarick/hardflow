<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->boolean('current_slot')->nullable()->after('is_active');
            $table->unique(['product_unit_id', 'price_level_id', 'current_slot'], 'product_prices_one_current_unique');
        });
        DB::table('product_prices')->where('is_active', true)->update(['current_slot' => true]);
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropUnique('product_prices_one_current_unique');
            $table->dropColumn('current_slot');
        });
    }
};
