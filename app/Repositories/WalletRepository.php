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
        $pdo = Database::pdo();
        $sql = 'SELECT w.*, u.username, u.email, c.code AS currency_code, c.symbol AS currency_symbol,
                       wt.slug AS type_slug, wt.label AS type_label, wt.icon AS type_icon
                FROM wallets w
                JOIN users u ON u.id = w.user_id
                JOIN currencies c ON c.id = w.currency_id
                JOIN wallet_types wt ON wt.id = w.wallet_type_id';
        $params = [];
        if ($userIdFilter !== null && $userIdFilter > 0) {
            $sql .= ' WHERE w.user_id = ?';
            $params[] = $userIdFilter;
        }
        $sql .= ' ORDER BY u.username ASC, w.sort_order ASC, w.id ASC';
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

    /** @return array<string, mixed>|null */
    public function findByIdForAdmin(int $walletId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT w.*, u.username, u.email, c.code AS currency_code,
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
