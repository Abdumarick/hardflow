<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('businesses')->pluck('id') as $businessId) {
            DB::table('business_settings')->insertOrIgnore(['business_id' => $businessId, 'key' => 'credit_limit_policy', 'value' => json_encode('block'), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('business_settings')->where('key', 'credit_limit_policy')->delete();
    }
};
