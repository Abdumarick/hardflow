<?php

namespace App\Support;

class PhoneNumber
{
    /** Convert a Tanzanian mobile number to the canonical 255XXXXXXXXX form. */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! str_starts_with($digits, '255')) {
            $digits = '255'.$digits;
        }

        return preg_match('/^255\d{9}$/', $digits) ? $digits : null;
    }

    public static function display(string $phone): string
    {
        return str_starts_with($phone, '255') ? '0'.substr($phone, 3) : $phone;
    }
}
