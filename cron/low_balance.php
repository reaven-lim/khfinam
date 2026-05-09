<?php

declare(strict_types=1);

/**
 * CLI: php cron/low_balance.php
 * Crontab: 0 8 * * * cd /path/to/khfinam && php cron/low_balance.php
 */

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

use App\Services\LowBalanceNotifier;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$n = (new LowBalanceNotifier())->run();
echo "Created {$n} low-balance notification(s).\n";
