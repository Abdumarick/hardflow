<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('businesses')->orderBy('id')->eachById(function (object $business) use ($now): void {
            foreach (['Lipa kwa M-Pesa', 'Airtel Money', 'Tigo Pesa', 'HaloPesa', 'Other Mobile Money'] as $name) {
                DB::table('payment_methods')->updateOrInsert(
                    ['business_id' => $business->id, 'name' => $name],
                    ['type' => 'mobile_money', 'requires_reference' => true, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now],
                );
            }
        });
    }

    public function down(): void
    {
        DB::table('payment_methods')->whereIn('name', ['Lipa kwa M-Pesa', 'Airtel Money', 'Tigo Pesa', 'HaloPesa', 'Other Mobile Money'])->delete();
    }
};
