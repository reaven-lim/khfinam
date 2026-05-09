<?php

declare(strict_types=1);

/**
 * CLI backup: mysqldump required on PATH or set MYSQLDUMP path.
 * Example crontab: 15 3 * * * cd /path/to/khfinam && php cron/backup.php
 */

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

use App\Helpers\Config;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$db = Config::get('db');
if (! is_array($db)) {
    fwrite(STDERR, "Missing DB config.\n");
    exit(1);
}

$dir = $root . '/storage/backups';
if (! is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$name = 'khfinam_' . date('Y-m-d_His') . '.sql.gz';
$path = $dir . '/' . $name;

$host = escapeshellarg((string) $db['host']);
$user = escapeshellarg((string) $db['username']);
$pass = (string) $db['password'];
$database = escapeshellarg((string) $db['database']);
$port = (int) ($db['port'] ?? 3306);

$env = '';
if ($pass !== '') {
    $env = 'MYSQL_PWD=' . escapeshellarg($pass) . ' ';
}

$cmd = sprintf(
    '%smysqldump --single-transaction --quick --host=%s --port=%d --user=%s %s | gzip > %s',
    $env,
    $host,
    $port,
    $user,
    $database,
    escapeshellarg($path)
);

exec($cmd, $out, $code);
if ($code !== 0 || ! is_file($path)) {
    fwrite(STDERR, "Backup failed. Ensure mysqldump is installed and credentials work.\n");
    exit(1);
}

$pdo = \App\Core\Database::pdo();
$size = filesize($path) ?: 0;
$pdo->prepare('INSERT INTO backups (filename, size_bytes, created_by, note) VALUES (?,?,NULL,?)')
    ->execute([$name, $size, 'cron']);

echo "Backup written: {$path}\n";
