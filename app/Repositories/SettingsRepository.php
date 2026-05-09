<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class SettingsRepository
{
    /** @return array<string, string> */
    public function getGlobal(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("SELECT key_name, value FROM settings WHERE scope = 'global' AND user_id IS NULL");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['key_name']] = (string) ($r['value'] ?? '');
        }

        return $out;
    }

    public function setGlobal(string $key, ?string $value): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM settings WHERE scope = \'global\' AND user_id IS NULL AND key_name = ? LIMIT 1');
        $stmt->execute([$key]);
        $id = $stmt->fetchColumn();
        if ($id) {
            $pdo->prepare('UPDATE settings SET value = ?, updated_at = NOW() WHERE id = ?')->execute([$value, $id]);
        } else {
            $pdo->prepare('INSERT INTO settings (scope, user_id, key_name, value) VALUES (\'global\', NULL, ?, ?)')->execute([$key, $value]);
        }
    }

    /** @param array<string, string|null> $pairs */
    public function setManyGlobal(array $pairs): void
    {
        foreach ($pairs as $k => $v) {
            $this->setGlobal((string) $k, $v === null ? null : (string) $v);
        }
    }
}
