<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
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
        $stmt = $pdo->prepare('SELECT id, username, email, full_name, role, is_active, last_login_at, created_at FROM users ORDER BY id ASC LIMIT ? OFFSET ?');
        $stmt->execute([$limit, $offset]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            'INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['full_name'] ?? null,
            $data['role'] ?? 'user',
            $data['is_active'] ?? 1,
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
        if ($fields === []) {
            return;
        }
        $params[] = $userId;
        $sql = 'UPDATE users SET ' . implode(',', $fields) . ', updated_at = NOW() WHERE id = ?';
        $pdo->prepare($sql)->execute($params);
    }
}

