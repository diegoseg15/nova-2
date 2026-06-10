<?php

require_once __DIR__ . '/../app/core/env.php';

define('APP_NAME', env('APP_NAME', 'NOVA2'));
define('APP_ENV', env('APP_ENV', 'local'));
define('APP_DEBUG', env_bool('APP_DEBUG', false));
define('APP_TIMEZONE', env('APP_TIMEZONE', 'America/Guayaquil'));
define('APP_URL', env('APP_URL', 'http://localhost/nova1'));

date_default_timezone_set(APP_TIMEZONE);

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}
