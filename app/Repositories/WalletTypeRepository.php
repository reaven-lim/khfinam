<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class WalletTypeRepository
{
    /** @return array<int, array<string, mixed>> */
    public function allOrdered(bool $activeOnly = false): array
    {
        $pdo = Database::pdo();
        $sql = 'SELECT * FROM wallet_types';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM wallet_types WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function idBySlug(string $slug): ?int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM wallet_types WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function countWalletsUsingType(int $typeId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM wallets WHERE wallet_type_id = ?');
        $stmt->execute([$typeId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM wallet_types WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array{name:string,slug:string,label:string,icon?:string,sort_order?:int,is_active?:bool,is_system?:bool} $data */
    public function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO wallet_types (slug, label, icon, sort_order, is_active, is_system)
             VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['slug'],
            $data['label'],
            $data['icon'] ?? 'wallet',
            (int) ($data['sort_order'] ?? 50),
            ! empty($data['is_active']) ? 1 : 0,
            ! empty($data['is_system']) ? 1 : 0,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '') {
            throw new \InvalidArgumentException('Label required.');
        }
        $pdo = Database::pdo();
        $pdo->prepare(
            'UPDATE wallet_types SET label = ?, icon = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?'
        )->execute([
            $label,
            $data['icon'] ?? 'wallet',
            (int) ($data['sort_order'] ?? 0),
            ! empty($data['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    public function deleteCustom(int $id): void
    {
        $pdo = Database::pdo();
        $chk = $pdo->prepare('SELECT is_system FROM wallet_types WHERE id = ? LIMIT 1');
        $chk->execute([$id]);
        $sys = $chk->fetchColumn();
        if ($sys === false || (int) $sys === 1) {
            throw new \InvalidArgumentException('Cannot delete system wallet types.');
        }
        if ($this->countWalletsUsingType($id) > 0) {
            throw new \InvalidArgumentException('Cannot delete a type that is assigned to wallets.');
        }
        $pdo->prepare('DELETE FROM wallet_types WHERE id = ?')->execute([$id]);
    }
}
