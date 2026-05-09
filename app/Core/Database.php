<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Config;
use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $db = Config::get('db');
        if (!is_array($db)) {
            throw new PDOException('Database configuration missing.');
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['database'],
            $db['charset']
        );
        self::$pdo = new PDO($dsn, $db['username'], $db['password'], $db['options'] ?? []);

        return self::$pdo;
    }

    public static function disconnect(): void
    {
        self::$pdo = null;
    }
}
