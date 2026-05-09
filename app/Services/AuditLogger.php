<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;

final class AuditLogger
{
    public static function log(
        string $action,
        ?int $userId,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $metadata = null
    ): void {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, metadata) VALUES (?,?,?,?,?,?,?)'
            );
            $metaJson = $metadata !== null ? json_encode($metadata, JSON_THROW_ON_ERROR) : null;
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                Request::ip(),
                Request::userAgent(),
                $metaJson,
            ]);
        } catch (\Throwable) {
            // avoid breaking flow if audit table missing
        }
    }
}
