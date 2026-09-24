<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('phone')->orderBy('id')->each(function (object $user): void {
            $digits = preg_replace('/\D+/', '', (string) $user->phone);

            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }

            if (! str_starts_with($digits, '255')) {
                $digits = '255'.$digits;
            }

            if (preg_match('/^255\d{9}$/', $digits)) {
                DB::table('users')->where('id', $user->id)->update(['phone' => $digits]);
            }
        });
    }

    public function down(): void
    {
        // The previous phone format cannot be recovered reliably.
    }
};
