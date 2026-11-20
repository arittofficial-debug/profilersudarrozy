<?php

return [

    /*
    |--------------------------------------------------------------------------
    | UserGate Integration
    |--------------------------------------------------------------------------
    |
    | UserGate adalah identity provider yang menentukan siapa user dan apakah
    | user boleh login. Role dan permission tetap dikelola di aplikasi ini.
    |
    */

    'enabled' => filter_var(env('USERGATE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'base_url' => rtrim((string) env('USERGATE_BASE_URL', 'https://usergate.sandalgurun.web.id'), '/'),

    'api_url' => rtrim((string) env(
        'USERGATE_API_URL',
        env('USERGATE_BASE_URL', 'https://usergate.sandalgurun.web.id') . '/api/v1'
    ), '/'),

    'api_key' => (string) env('USERGATE_API_KEY', ''),

    // Timeout HTTP request ke UserGate dalam detik.
    'timeout' => (int) env('USERGATE_TIMEOUT', 10),

    // Interval validasi ulang sesi lokal terhadap UserGate (menit).
    'sync_interval' => (int) env('USERGATE_SYNC_INTERVAL', 5),

    // Nilai role pada response UserGate yang menandakan Super Admin.
    'super_admin_role' => strtoupper((string) env('USERGATE_SUPER_ADMIN_ROLE', 'SUPER_ADMIN')),

    /*
    |--------------------------------------------------------------------------
    | Akun Super Admin Lokal (fallback)
    |--------------------------------------------------------------------------
    |
    | Akun ini dibuat oleh seeder dan hanya dipakai sebagai fallback ketika
    | UserGate tidak dapat dihubungi. Status aktifnya ditentukan oleh env.
    |
    */

    'local_super_admin' => [
        'email' => strtolower(trim((string) env('LOCAL_SUPERADMIN_EMAIL', 'admin@company.com'))),
        'password' => (string) env('LOCAL_SUPERADMIN_PASSWORD', ''),
        'enabled' => filter_var(env('LOCAL_SUPERADMIN_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
