<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Config;

final class BackupService
{
    /** @return array{path: string, filename: string, size: int} */
    public function createSqlGz(?int $createdBy = null): array
    {
        $root = dirname(__DIR__, 2);
        $dir = $root . '/storage/backups';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $db = Config::get('db');
        if (! is_array($db)) {
            throw new \RuntimeException('DB config missing.');
        }

        $name = 'khfinam_' . date('Y-m-d_His') . '.sql.gz';
        $path = $dir . '/' . $name;

        $host = escapeshellarg((string) $db['host']);
        $user = escapeshellarg((string) $db['username']);
        $pass = (string) $db['password'];
        $database = escapeshellarg((string) $db['database']);
        $port = (int) ($db['port'] ?? 3306);

        $env = $pass !== '' ? 'MYSQL_PWD=' . escapeshellarg($pass) . ' ' : '';
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
            throw new \RuntimeException('mysqldump failed. Ensure mysqldump is on PATH.');
        }

        $size = (int) filesize($path);
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO backups (filename, size_bytes, created_by, note) VALUES (?,?,?,?)')
            ->execute([$name, $size, $createdBy, 'web']);

        return ['path' => $path, 'filename' => $name, 'size' => $size];
    }
}
