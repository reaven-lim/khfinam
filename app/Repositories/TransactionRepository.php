<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class TransactionRepository
{
    private const FLOW_EXCLUDE_INTERNAL = " AND COALESCE(is_internal_transfer,0) = 0 ";

    /** @return array{income: float, expense: float, savings: float} */
    public function globalTotals(?string $from = null, ?string $to = null, bool $excludeInternal = true): array
    {
        $pdo = Database::pdo();
        $sql = "SELECT type, COALESCE(SUM(amount_base),0) AS s FROM transactions WHERE deleted_at IS NULL AND parent_transaction_id IS NULL";
        if ($excludeInternal) {
            $sql .= self::FLOW_EXCLUDE_INTERNAL;
        }
        $params = [];
        if ($from) {
            $sql .= ' AND transaction_date >= ?';
            $params[] = $from;
        }
        if ($to) {
            $sql .= ' AND transaction_date <= ?';
            $params[] = $to;
        }
        $sql .= ' GROUP BY type';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $this->mapIncomeExpense($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array{income: float|int, expense: float|int, savings: float|int} */
    public function totalsForUser(int $userId, ?string $from = null, ?string $to = null, bool $excludeInternal = true): array
    {
        $pdo = Database::pdo();
        $sql = "SELECT type, COALESCE(SUM(amount_base),0) AS s FROM transactions WHERE user_id = ? AND deleted_at IS NULL AND parent_transaction_id IS NULL";
        if ($excludeInternal) {
            $sql .= self::FLOW_EXCLUDE_INTERNAL;
        }
        $params = [$userId];
        if ($from) {
            $sql .= ' AND transaction_date >= ?';
            $params[] = $from;
        }
        if ($to) {
            $sql .= ' AND transaction_date <= ?';
            $params[] = $to;
        }
        $sql .= ' GROUP BY type';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $this->mapIncomeExpense($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @param array<int, array<string, mixed>> $rows */
    /** @return array{income: float, expense: float, savings: float} */
    private function mapIncomeExpense(array $rows): array
    {
        $income = 0.0;
        $expense = 0.0;
        foreach ($rows as $r) {
            if ($r['type'] === 'income') {
                $income = (float) $r['s'];
            }
            if ($r['type'] === 'expense') {
                $expense = (float) $r['s'];
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'savings' => $income - $expense,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function recentForUser(int $userId, int $limit = 20): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT t.*, w.name AS wallet_name, c.name AS category_name
             FROM transactions t
             JOIN wallets w ON w.id = t.wallet_id
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = ? AND t.deleted_at IS NULL AND t.parent_transaction_id IS NULL
             ORDER BY t.transaction_date DESC, t.id DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function findByIdForUser(int $id, int $userId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT t.*, w.name AS wallet_name, c.name AS category_name FROM transactions t
             JOIN wallets w ON w.id = t.wallet_id
             JOIN categories c ON c.id = t.category_id
             WHERE t.id = ? AND t.user_id = ? AND t.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function childrenForParent(int $parentId, int $userId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT t.*, w.name AS wallet_name, c.name AS category_name FROM transactions t
             JOIN wallets w ON w.id = t.wallet_id
             JOIN categories c ON c.id = t.category_id
             WHERE t.parent_transaction_id = ? AND t.user_id = ? AND t.deleted_at IS NULL ORDER BY t.id ASC'
        );
        $stmt->execute([$parentId, $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sumChildrenBase(int $parentId, int $userId): float
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(amount_base),0) FROM transactions WHERE parent_transaction_id = ? AND user_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$parentId, $userId]);

        return (float) $stmt->fetchColumn();
    }

    /** Heatmap: day-of-year style counts per calendar day (expense amounts) */
    /** @return array<string, float> date => sum base */
    public function heatmapExpensesForUser(int $userId, int $year): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT transaction_date AS d, COALESCE(SUM(amount_base),0) AS s
             FROM transactions
             WHERE user_id = ? AND deleted_at IS NULL AND parent_transaction_id IS NULL
               AND type = 'expense' AND COALESCE(is_internal_transfer,0) = 0
               AND YEAR(transaction_date) = ?
             GROUP BY transaction_date"
        );
        $stmt->execute([$userId, $year]);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[(string) $row['d']] = (float) $row['s'];
        }

        return $out;
    }
}
