<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CategoryRepository
{
    /** @return array<int, array<string, mixed>> */
    public function forUserIncludingGlobal(int $userId, ?string $type = null): array
    {
        $pdo = Database::pdo();
        $sql = 'SELECT * FROM categories WHERE user_id IS NULL OR user_id = ?';
        $params = [$userId];
        if ($type !== null) {
            $sql .= ' AND type = ?';
            $params[] = $type;
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
