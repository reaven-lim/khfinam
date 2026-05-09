<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;

/**
 * Restores MySQL from a plain .sql dump using the mysql CLI.
 */
final class DatabaseRestoreService
{
    public function restoreFromFile(string $absSqlPath): void
    {
        if (! is_readable($absSqlPath)) {
            throw new \InvalidArgumentException('SQL file not readable.');
        }

        $db = Config::get('db');
        if (! is_array($db)) {
            throw new \RuntimeException('Database config missing.');
        }

        $mysql = $this->resolveMysqlBinary();
        $host = (string) $db['host'];
        $port = (int) ($db['port'] ?? 3306);
        $user = (string) $db['username'];
        $pass = (string) $db['password'];
        $database = (string) $db['database'];

        $cmd = [
            $mysql,
            '--host=' . $host,
            '--port=' . (string) $port,
            '--user=' . $user,
            '--default-character-set=utf8mb4',
            $database,
        ];
        $env = $_ENV;
        if ($pass !== '') {
            $env['MYSQL_PWD'] = $pass;
        }

        $desc = [
            0 => ['file', $absSqlPath, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $desc, $pipes, null, $env);
        if (! is_resource($proc)) {
            throw new \RuntimeException('Could not start mysql process.');
        }
        fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0) {
            throw new \RuntimeException('mysql restore failed (code ' . $code . '): ' . $err);
        }
    }

    private function resolveMysqlBinary(): string
    {
        $env = getenv('MYSQL_BIN');
        if (is_string($env) && $env !== '' && is_file($env)) {
            return $env;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['C:\\xampp\\mysql\\bin\\mysql.exe', 'D:\\xampp\\mysql\\bin\\mysql.exe'] as $c) {
                if (is_file($c)) {
                    return $c;
                }
            }
        }
        exec(PHP_OS_FAMILY === 'Windows' ? 'where mysql 2>nul' : 'which mysql 2>/dev/null', $o, $rc);
        if ($rc === 0 && ! empty($o[0])) {
            $p = trim($o[0]);
            if (is_file($p)) {
                return $p;
            }
        }

        throw new \RuntimeException('mysql client not found. Set MYSQL_BIN or install MySQL client.');
    }

    /** Decompress .sql.gz to a temp file and restore. */
    public function restoreFromGzFile(string $absGzPath): void
    {
        if (! is_readable($absGzPath)) {
            throw new \InvalidArgumentException('Backup file not readable.');
        }
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'khfinam_restore_' . bin2hex(random_bytes(8)) . '.sql';
        $in = @gzopen($absGzPath, 'rb');
        if ($in === false) {
            throw new \RuntimeException('Could not open gzip backup.');
        }
        $out = fopen($tmp, 'wb');
        if ($out === false) {
            gzclose($in);
            throw new \RuntimeException('Temp file error.');
        }
        while (! gzeof($in)) {
            fwrite($out, gzread($in, 1024 * 512));
        }
        gzclose($in);
        fclose($out);
        try {
            $this->restoreFromFile($tmp);
        } finally {
            @unlink($tmp);
        }
    }
}
