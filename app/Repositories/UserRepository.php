<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    /**
     * Scoped to users whose ledger appears in admin/global analytics (subquery).
     * Use as: "user_id IN (" . self::analyticsIncludedUserIdsSubquery() . ")"
     */
    public static function analyticsIncludedUserIdsSubquery(): string
    {
        return 'SELECT id FROM users WHERE include_in_analytics = 1';
    }

    public static function analyticsScopeTransactionUserSql(string $alias = 't'): string
    {
        return $alias . '.user_id IN (' . self::analyticsIncludedUserIdsSubquery() . ')';
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByUsernameOrEmail(string $login): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
        $stmt->execute([$login, $login]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(int $limit = 500, int $offset = 0): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, username, email, full_name, role, is_active, include_in_analytics, last_login_at, created_at FROM users ORDER BY id ASC LIMIT ? OFFSET ?');
        $stmt->execute([$limit, $offset]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Admin directory rows with lightweight aggregates (balances added in controller).
     *
     * @return array<int, array<string, mixed>>
     */
    /**
     * @param 'all'|'included'|'excluded' $analyticsFilter
     * @return array<int, array<string, mixed>>
     */
    public function adminUserDirectoryRows(int $limit = 500, int $offset = 0, string $analyticsFilter = 'all'): array
    {
        $pdo = Database::pdo();
        $lim = max(1, min(2000, $limit));
        $off = max(0, $offset);
        $where = '';
        if ($analyticsFilter === 'included') {
            $where = ' WHERE u.include_in_analytics = 1';
        } elseif ($analyticsFilter === 'excluded') {
            $where = ' WHERE u.include_in_analytics = 0';
        }
        $sql = 'SELECT u.id, u.username, u.email, u.full_name, u.role, u.is_active, u.include_in_analytics, u.last_login_at, u.created_at,
            (SELECT COUNT(*) FROM wallets w WHERE w.user_id = u.id) AS wallet_count,
            (SELECT COUNT(*) FROM recurring_schedules r WHERE r.user_id = u.id AND r.is_paused = 0
                AND (r.end_date IS NULL OR r.end_date >= CURDATE())) AS recurring_active_count,
            (SELECT MAX(t.transaction_date) FROM transactions t WHERE t.user_id = u.id AND t.deleted_at IS NULL) AS last_transaction_date
            FROM users u' . $where . '
            ORDER BY u.id DESC
            LIMIT ' . $lim . ' OFFSET ' . $off;
        $stmt = $pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * KPIs for the admin user management overview (counts only; low-balance tally computed elsewhere).
     *
     * @return array{
     *   total:int,
     *   active:int,
     *   inactive:int,
     *   analytics_included:int,
     *   analytics_excluded:int,
     *   new_this_month:int,
     *   new_last_7d:int,
     *   wallet_count:int,
     *   recurring_users:int,
     *   recent_registrations: array<int, array<string, mixed>>,
     *   signup_by_month: array<int, array<string, mixed>>
     * }
     */
    public function adminUserManagementKpis(): array
    {
        $pdo = Database::pdo();
        $sub = self::analyticsIncludedUserIdsSubquery();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $analyticsIncluded = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE include_in_analytics = 1')->fetchColumn();
        $analyticsExcluded = max(0, $total - $analyticsIncluded);
        $active = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
        $inactive = $total - $active;
        $newThisMonth = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE include_in_analytics = 1
             AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
        )->fetchColumn();
        $newLast7d = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE include_in_analytics = 1 AND created_at >= (NOW() - INTERVAL 7 DAY)"
        )->fetchColumn();
        $walletCount = (int) $pdo->query(
            'SELECT COUNT(*) FROM wallets WHERE user_id IN (' . $sub . ')'
        )->fetchColumn();
        $recurringUsers = (int) $pdo->query(
            'SELECT COUNT(DISTINCT user_id) FROM recurring_schedules
             WHERE user_id IN (' . $sub . ')
               AND is_paused = 0 AND (end_date IS NULL OR end_date >= CURDATE())'
        )->fetchColumn();
        $recent = $pdo->query(
            'SELECT id, username, email, role, is_active, include_in_analytics, created_at FROM users
             WHERE include_in_analytics = 1 ORDER BY id DESC LIMIT 6'
        )->fetchAll(PDO::FETCH_ASSOC);
        $signupRows = $pdo->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c FROM users
             WHERE include_in_analytics = 1 GROUP BY ym ORDER BY ym DESC LIMIT 6"
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => max(0, $inactive),
            'analytics_included' => $analyticsIncluded,
            'analytics_excluded' => $analyticsExcluded,
            'new_this_month' => $newThisMonth,
            'new_last_7d' => $newLast7d,
            'wallet_count' => $walletCount,
            'recurring_users' => $recurringUsers,
            'recent_registrations' => $recent,
            'signup_by_month' => array_reverse($signupRows),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function auditLogsRelatedToUser(int $targetUserId, string $username, int $limit = 45): array
    {
        $pdo = Database::pdo();
        $lim = max(1, min(200, $limit));
        $idStr = (string) $targetUserId;
        $stmt = $pdo->prepare(
            'SELECT * FROM audit_logs
             WHERE (entity_type = ? AND (entity_id = ? OR entity_id = ?))
                OR user_id = ?
             ORDER BY id DESC
             LIMIT ' . $lim
        );
        $stmt->execute(['user', $idStr, $username, $targetUserId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateNotificationThemePreferences(int $userId, string $theme, bool $muteLowBalance): void
    {
        if (! in_array($theme, ['light', 'dark', 'system'], true)) {
            $theme = 'system';
        }
        $pdo = Database::pdo();
        $pdo->prepare(
            'UPDATE users SET preference_theme = ?, preference_mute_low_balance = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$theme, $muteLowBalance ? 1 : 0, $userId]);
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([$hash, $userId]);
    }

    public function setRememberToken(int $userId, ?string $token, ?string $expires): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE users SET remember_token = ?, remember_expires_at = ? WHERE id = ?')
            ->execute([$token, $expires, $userId]);
    }

    /** @return array<string, mixed>|null */
    public function findByRememberToken(string $rawCookieToken): ?array
    {
        $stored = hash('sha256', $rawCookieToken);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE remember_token = ? AND is_active = 1 AND (remember_expires_at IS NULL OR remember_expires_at > NOW()) LIMIT 1');
        $stmt->execute([$stored]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateLastLogin(int $userId): void
    {
        Database::pdo()->prepare('UPDATE users SET last_login_at = NOW(), failed_login_attempts = 0 WHERE id = ?')->execute([$userId]);
    }

    public function incrementFailedLogin(int $userId): void
    {
        Database::pdo()->prepare('UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?')->execute([$userId]);
    }

    public function lockAccount(int $userId, \DateTimeInterface $until): void
    {
        Database::pdo()->prepare('UPDATE users SET locked_until = ? WHERE id = ?')
            ->execute([$until->format('Y-m-d H:i:s'), $userId]);
    }

    public function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role, is_active, include_in_analytics) VALUES (?,?,?,?,?,?,?)'
        );
        $includeAnalytics = array_key_exists('include_in_analytics', $data)
            ? filter_var($data['include_in_analytics'], FILTER_VALIDATE_BOOLEAN)
            : true;
        $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['full_name'] ?? null,
            $data['role'] ?? 'user',
            $data['is_active'] ?? 1,
            $includeAnalytics ? 1 : 0,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findByIdAny(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function update(int $userId, array $data): void
    {
        $pdo = Database::pdo();
        $fields = [];
        $params = [];
        if (array_key_exists('email', $data)) {
            $fields[] = 'email = ?';
            $params[] = $data['email'];
        }
        if (array_key_exists('full_name', $data)) {
            $fields[] = 'full_name = ?';
            $params[] = $data['full_name'];
        }
        if (array_key_exists('role', $data)) {
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }
        if (array_key_exists('is_active', $data)) {
            $fields[] = 'is_active = ?';
            $params[] = (int) ! empty($data['is_active']);
        }
        if (array_key_exists('include_in_analytics', $data)) {
            $fields[] = 'include_in_analytics = ?';
            $params[] = filter_var($data['include_in_analytics'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if ($fields === []) {
            return;
        }
        $params[] = $userId;
        $sql = 'UPDATE users SET ' . implode(',', $fields) . ', updated_at = NOW() WHERE id = ?';
        $pdo->prepare($sql)->execute($params);
    }
}

