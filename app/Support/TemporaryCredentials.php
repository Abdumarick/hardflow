<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class TemporaryCredentials
{
    public static function password(string $name): string
    {
        $firstName = Str::of($name)->trim()->explode(' ')->first();
        $firstName = Str::of($firstName)->ascii()->replaceMatches('/[^A-Za-z]/', '')->ucfirst()->value();

        return ($firstName !== '' ? $firstName : 'Staff').'-'.Str::password(12, true, true, false, false);
    }

    public static function username(string $name): string
    {
        $base = Str::of($name)->trim()->explode(' ')->first();
        $base = Str::of($base)->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '')->value();
        $base = $base !== '' ? Str::limit($base, 16, '') : 'staff';

        do {
            $username = $base.random_int(100, 999);
        } while (User::query()->where('username', $username)->exists());

        return $username;
    }
}
