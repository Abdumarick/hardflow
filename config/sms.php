<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sewmr SMS
    |--------------------------------------------------------------------------
    |
    | Keep SMS disabled until the provider settings below have been configured
    | in the environment. Credentials must never be committed to source code.
    |
    */
    'enabled' => (bool) env('SMS_ENABLED', false),
    'url' => rtrim((string) env('SMS_API_URL', 'https://api.sewmrsms.co.tz/api/v1'), '/'),
    'token' => env('SMS_API_TOKEN'),
    // Sewmr requires the UUID of an approved sender ID, not its display name.
    'sender_id' => env('SMS_SENDER_ID'),
];
