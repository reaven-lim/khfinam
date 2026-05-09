<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

\App\Core\Env::load($root . '/.env');

$config = require $root . '/config/app.php';
$db = require $root . '/config/database.php';

\App\Helpers\Config::setArray('app', $config);
\App\Helpers\Config::setArray('db', $db);

date_default_timezone_set(\App\Helpers\Config::get('app.timezone', 'UTC'));

if (\App\Helpers\Config::get('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}
