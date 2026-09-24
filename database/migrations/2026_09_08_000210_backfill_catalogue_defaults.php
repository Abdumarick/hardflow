<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('businesses')->orderBy('id')->each(function (object $business): void {
            foreach ([['Retail', 'RETAIL', true], ['Wholesale', 'WHOLESALE', false]] as [$name, $code, $isDefault]) {
                DB::table('price_levels')->insertOrIgnore([
                    'business_id' => $business->id, 'public_id' => (string) Str::uuid(), 'name' => $name,
                    'code' => $code, 'is_default' => $isDefault, 'is_system' => true, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $branch = DB::table('branches')->where('business_id', $business->id)->orderByDesc('is_main')->orderBy('id')->first();
            if ($branch) {
                DB::table('number_sequences')->insertOrIgnore([
                    'business_id' => $business->id, 'branch_id' => $branch->id, 'type' => 'product',
                    'prefix' => $business->code.'-PRD-', 'next_number' => 1, 'padding' => 6,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Defaults may already be in use; they are intentionally retained on rollback.
    }
};
