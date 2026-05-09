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

    /**
     * Same filter semantics as AdminDashboardController::transactions() listing.
     *
     * @return array{where: string, params: array<int, string|int>}
     */
    public function adminTransactionsFilter(int $userId, string $from, string $to, string $type): array
    {
        $clauses = ['t.deleted_at IS NULL'];
        $params = [];
        if ($userId > 0) {
            $clauses[] = 't.user_id = ?';
            $params[] = $userId;
        }
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $clauses[] = 't.transaction_date >= ?';
            $params[] = $from;
        }
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $clauses[] = 't.transaction_date <= ?';
            $params[] = $to;
        }
        if ($type === 'income' || $type === 'expense') {
            $clauses[] = 't.type = ?';
            $params[] = $type;
        }

        return [
            'where' => implode(' AND ', $clauses),
            'params' => $params,
        ];
    }

    /** @return array{count: int, income: float, expense: float, avg_abs: float} */
    public function adminFilteredSummary(int $userId, string $from, string $to, string $type): array
    {
        $f = $this->adminTransactionsFilter($userId, $from, $to, $type);
        $pdo = Database::pdo();
        $sql = 'SELECT COUNT(*) AS cnt,
            COALESCE(SUM(CASE WHEN t.type = \'income\' THEN t.amount_base ELSE 0 END), 0) AS income,
            COALESCE(SUM(CASE WHEN t.type = \'expense\' THEN t.amount_base ELSE 0 END), 0) AS expense,
            COALESCE(AVG(ABS(t.amount_base)), 0) AS avg_abs
            FROM transactions t WHERE ' . $f['where'];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($f['params']);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'count' => (int) ($r['cnt'] ?? 0),
            'income' => (float) ($r['income'] ?? 0),
            'expense' => (float) ($r['expense'] ?? 0),
            'avg_abs' => (float) ($r['avg_abs'] ?? 0),
        ];
    }

    /** @return array{name: string, total: float}|null */
    public function adminFilteredTopCategory(int $userId, string $from, string $to, string $type): ?array
    {
        $wantType = $type === 'income' ? 'income' : 'expense';
        if ($type !== '' && $type !== $wantType) {
            return null;
        }
        $f = $this->adminTransactionsFilter($userId, $from, $to, $type === '' ? '' : $wantType);
        $pdo = Database::pdo();
        $sql = 'SELECT c.name AS name, COALESCE(SUM(t.amount_base), 0) AS total
            FROM transactions t JOIN categories c ON c.id = t.category_id
            WHERE ' . $f['where'] . ' AND t.type = ? GROUP BY c.id ORDER BY total DESC LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($f['params'], [$wantType]));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $row || (float) $row['total'] <= 0) {
            return null;
        }

        return ['name' => (string) $row['name'], 'total' => (float) $row['total']];
    }

    /**
     * Extra date window on top of user/type (for charts). Uses inclusive bounds.
     *
     * @return array<int, array{d: string, income: float, expense: float, volume: int}>
     */
    public function adminFilteredDailySeries(int $userId, string $from, string $to, string $type, string $seriesFrom, string $seriesTo): array
    {
        $f = $this->adminTransactionsFilter($userId, $from, $to, $type);
        $pdo = Database::pdo();
        $sql = 'SELECT t.transaction_date AS d,
            COALESCE(SUM(CASE WHEN t.type = \'income\' THEN t.amount_base ELSE 0 END), 0) AS income,
            COALESCE(SUM(CASE WHEN t.type = \'expense\' THEN t.amount_base ELSE 0 END), 0) AS expense,
            COUNT(*) AS volume
            FROM transactions t
            WHERE ' . $f['where'] . ' AND t.transaction_date >= ? AND t.transaction_date <= ?
            GROUP BY t.transaction_date ORDER BY t.transaction_date ASC';
        $params = array_merge($f['params'], [$seriesFrom, $seriesTo]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array{name: string, total: float}> */
    public function adminFilteredCategoryBreakdown(int $userId, string $from, string $to, string $type, string $seriesFrom, string $seriesTo): array
    {
        $breakType = ($type === 'income') ? 'income' : 'expense';
        $f = $this->adminTransactionsFilter($userId, $from, $to, $type);
        $pdo = Database::pdo();
        $sql = 'SELECT c.name AS name, COALESCE(SUM(t.amount_base), 0) AS total
            FROM transactions t JOIN categories c ON c.id = t.category_id
            WHERE ' . $f['where'] . ' AND t.type = ? AND t.transaction_date >= ? AND t.transaction_date <= ?
            GROUP BY c.id HAVING COALESCE(SUM(t.amount_base), 0) > 0 ORDER BY total DESC LIMIT 10';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($f['params'], [$breakType, $seriesFrom, $seriesTo]));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array{name: string, total: float, volume: int}> */
    public function adminFilteredWalletBreakdown(int $userId, string $from, string $to, string $type, string $seriesFrom, string $seriesTo): array
    {
        $f = $this->adminTransactionsFilter($userId, $from, $to, $type);
        $pdo = Database::pdo();
        $sql = 'SELECT w.name AS name, COALESCE(SUM(t.amount_base), 0) AS total,
            COUNT(*) AS volume FROM transactions t
            JOIN wallets w ON w.id = t.wallet_id
            WHERE ' . $f['where'] . ' AND t.transaction_date >= ? AND t.transaction_date <= ?
            GROUP BY w.id ORDER BY total DESC LIMIT 10';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($f['params'], [$seriesFrom, $seriesTo]));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Expense totals for two consecutive windows (same length), for trend copy. */
    public function adminFilteredExpenseWindow(int $userId, string $from, string $to, string $type, string $winStart, string $winEnd): float
    {
        $f = $this->adminTransactionsFilter($userId, $from, $to, $type === '' ? '' : $type);
        $pdo = Database::pdo();
        if ($type === 'income') {
            return 0.0;
        }
        $typeClause = ($type === 'expense') ? ' AND t.type = \'expense\'' : ' AND t.type = \'expense\'';
        $sql = 'SELECT COALESCE(SUM(t.amount_base), 0) FROM transactions t WHERE ' . $f['where']
            . $typeClause . ' AND t.transaction_date >= ? AND t.transaction_date <= ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($f['params'], [$winStart, $winEnd]));

        return (float) $stmt->fetchColumn();
    }

    /** Share of rows linked to a recurring schedule (0–1). */
    public function adminFilteredRecurringRatio(int $userId, string $from, string $to, string $type): float
    {
        $f = $this->adminTransactionsFilter($userId, $from, $to, $type);
        $pdo = Database::pdo();
        $sql = 'SELECT COUNT(*) AS c, SUM(CASE WHEN t.recurring_schedule_id IS NOT NULL THEN 1 ELSE 0 END) AS r
            FROM transactions t WHERE ' . $f['where'];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($f['params']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['c' => 0, 'r' => 0];
        $c = (int) $row['c'];
        if ($c === 0) {
            return 0.0;
        }

        return (int) $row['r'] / $c;
    }

    /** @return array{name: string, total: float}|null */
    public function adminFilteredTopWallet(int $userId, string $from, string $to, string $type, string $seriesFrom, string $seriesTo): ?array
    {
        $rows = $this->adminFilteredWalletBreakdown($userId, $from, $to, $type, $seriesFrom, $seriesTo);
        if ($rows === []) {
            return null;
        }
        $top = $rows[0];

        return ['name' => (string) $top['name'], 'total' => (float) $top['total']];
    }
}
