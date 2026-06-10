<?php

require_once __DIR__ . '/../app/core/env.php';

define('APP_NAME', env('APP_NAME', 'NOVA2'));
define('APP_ENV', env('APP_ENV', 'local'));
define('APP_DEBUG', env_bool('APP_DEBUG', false));
define('APP_TIMEZONE', env('APP_TIMEZONE', 'America/Guayaquil'));
define('APP_URL', env('APP_URL', 'http://localhost/nova1'));

date_default_timezone_set(APP_TIMEZONE);
