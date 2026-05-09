<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class RateLimiter
{
    public static function tooMany(string $bucketKey, int $max, int $windowSeconds): bool
    {
        $now = time();
        $windowStart = $windowSeconds > 0 ? (int) (floor($now / $windowSeconds) * $windowSeconds) : $now;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO rate_limit_buckets (bucket_key, window_start, hits) VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE hits = hits + 1'
            );
            $stmt->execute([$bucketKey, $windowStart]);

            $q = $pdo->prepare('SELECT hits FROM rate_limit_buckets WHERE bucket_key = ? AND window_start = ? LIMIT 1');
            $q->execute([$bucketKey, $windowStart]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            $hits = (int) ($row['hits'] ?? 0);
            $pdo->commit();

            return $hits > $max;
        } catch (\Throwable) {
            $pdo->rollBack();

            return false;
        }
    }
}
