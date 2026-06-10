<?php

require_once __DIR__ . '/../app/core/env.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(env('SESSION_NAME', 'NOVA2SESSID'));

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => env('APP_ENV', 'local') === 'production',
    ]);

    session_start();
}
