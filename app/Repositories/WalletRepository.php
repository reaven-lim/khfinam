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
        $sql = 'SELECT w.*, c.code AS currency_code, c.symbol AS currency_symbol,
                       wt.slug AS type_slug, wt.label AS type_label, wt.icon AS type_icon
                FROM wallets w
                JOIN currencies c ON c.id = w.currency_id
                JOIN wallet_types wt ON wt.id = w.wallet_type_id
                WHERE w.user_id = ?';
        if ($activeOnly) {
            $sql .= ' AND w.is_active = 1';
        }
        $sql .= ' ORDER BY w.sort_order ASC, w.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function findForOwner(int $walletId, int $userId, bool $includeInactive = true): ?array
    {
        $pdo = Database::pdo();
        $sql = 'SELECT w.*, c.code AS currency_code, c.symbol AS currency_symbol,
                       wt.slug AS type_slug, wt.label AS type_label, wt.icon AS type_icon
                FROM wallets w
                JOIN currencies c ON c.id = w.currency_id
                JOIN wallet_types wt ON wt.id = w.wallet_type_id
                WHERE w.id = ? AND w.user_id = ?';
        if (! $includeInactive) {
            $sql .= ' AND w.is_active = 1';
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$walletId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * All wallets platform-wide for super-admin (includes inactive unless filtered).
     *
     * @return array<int, array<string, mixed>>
     */
    public function adminListAll(?int $userIdFilter = null): array
    {
        return $this->adminListFiltered([
            'user_id' => $userIdFilter !== null && $userIdFilter > 0 ? $userIdFilter : 0,
            'wallet_type_id' => 0,
            'currency_id' => 0,
            'status' => 'all',
            'owner_analytics' => 'all',
            'search' => '',
        ], 2000, 0);
    }

    /**
     * Admin wallet directory with rich filters (balances added in controller).
     *
     * @param array{
     *   user_id?: int,
     *   wallet_type_id?: int,
     *   currency_id?: int,
     *   status?: 'all'|'active'|'inactive',
     *   owner_analytics?: 'all'|'included'|'excluded',
     *   search?: string
     * } $filters
     *
     * @return array<int, array<string, mixed>>
     */
    public function adminListFiltered(array $filters, int $limit = 800, int $offset = 0): array
    {
        $pdo = Database::pdo();
        $sql = 'SELECT w.*, u.username, u.email, u.include_in_analytics AS owner_include_in_analytics,
                       c.code AS currency_code, c.symbol AS currency_symbol,
                       wt.slug AS type_slug, wt.label AS type_label, wt.icon AS type_icon
                FROM wallets w
                JOIN users u ON u.id = w.user_id
                JOIN currencies c ON c.id = w.currency_id
                JOIN wallet_types wt ON wt.id = w.wallet_type_id
                WHERE 1=1';
        $params = [];
        $uid = (int) ($filters['user_id'] ?? 0);
        if ($uid > 0) {
            $sql .= ' AND w.user_id = ?';
            $params[] = $uid;
        }
        $wtid = (int) ($filters['wallet_type_id'] ?? 0);
        if ($wtid > 0) {
            $sql .= ' AND w.wallet_type_id = ?';
            $params[] = $wtid;
        }
        $curid = (int) ($filters['currency_id'] ?? 0);
        if ($curid > 0) {
            $sql .= ' AND w.currency_id = ?';
            $params[] = $curid;
        }
        $status = (string) ($filters['status'] ?? 'all');
        if ($status === 'active') {
            $sql .= ' AND w.is_active = 1';
        } elseif ($status === 'inactive') {
            $sql .= ' AND w.is_active = 0';
        }
        $ownAn = (string) ($filters['owner_analytics'] ?? 'all');
        if ($ownAn === 'included') {
            $sql .= ' AND u.include_in_analytics = 1';
        } elseif ($ownAn === 'excluded') {
            $sql .= ' AND u.include_in_analytics = 0';
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $sql .= ' AND (w.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $lim = max(1, min(2000, $limit));
        $off = max(0, $offset);
        $sql .= ' ORDER BY w.updated_at DESC, w.id DESC LIMIT ' . $lim . ' OFFSET ' . $off;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTransactions(int $walletId): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM transactions WHERE deleted_at IS NULL
             AND (wallet_id = ? OR from_wallet_id = ? OR to_wallet_id = ?)'
        );
        $stmt->execute([$walletId, $walletId, $walletId]);

        return (int) $stmt->fetchColumn();
    }

    public function countRecurring(int $walletId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM recurring_schedules WHERE wallet_id = ?');
        $stmt->execute([$walletId]);

        return (int) $stmt->fetchColumn();
    }

    public function recurringActiveCountForUser(int $userId): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM recurring_schedules
             WHERE user_id = ? AND is_paused = 0 AND (end_date IS NULL OR end_date >= CURDATE())'
        );
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public function findByIdForAdmin(int $walletId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT w.*, u.username, u.email, u.include_in_analytics AS owner_include_in_analytics,
                    c.code AS currency_code,
                    wt.slug AS type_slug, wt.label AS type_label, wt.icon AS type_icon
             FROM wallets w
             JOIN users u ON u.id = w.user_id
             JOIN currencies c ON c.id = w.currency_id
             JOIN wallet_types wt ON wt.id = w.wallet_type_id
             WHERE w.id = ? LIMIT 1'
        );
        $stmt->execute([$walletId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
