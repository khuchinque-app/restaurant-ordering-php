<?php
/**
 * All runtime config comes from environment variables.
 * Copy .env.example to .env and fill in values before starting.
 */

$_jwt = getenv('JWT_SECRET');
if (!$_jwt) {
    $msg = 'Fatal: JWT_SECRET environment variable is not set. '
         . 'Copy .env.example to .env and set a secure value.';
    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        header('Content-Type: text/plain');
    }
    exit($msg . PHP_EOL);
}
define('JWT_SECRET', $_jwt);
unset($_jwt);

define('DB_PATH',    getenv('DB_PATH')    ?: __DIR__ . '/data/restaurant.db');
define('APP_URL',    rtrim(getenv('APP_URL') ?: '', '/'));
define('TAX_RATE',   (float)(getenv('TAX_RATE') ?: 0.10));
define('DEFAULT_RESTAURANT_SLUG', getenv('DEFAULT_RESTAURANT_SLUG') ?: 'aseng');
