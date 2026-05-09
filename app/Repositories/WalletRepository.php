<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class WalletRepository
{
    /** @return array<int, array<string, mixed>> */
    public function forUser(int $userId, bool $activeOnly = true): array
    {
        $pdo = Database::pdo();
        $sql = 'SELECT w.*, c.code AS currency_code, c.symbol AS currency_symbol FROM wallets w JOIN currencies c ON c.id = w.currency_id WHERE w.user_id = ?';
        if ($activeOnly) {
            $sql .= ' AND w.is_active = 1';
        }
        $sql .= ' ORDER BY w.sort_order ASC, w.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
