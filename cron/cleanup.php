<?php

declare(strict_types=1);

/**
 * Cleanup cron — run daily (e.g. 02:00).
 *
 * Tasks:
 *  1. Hard-delete transactions soft-deleted more than 90 days ago.
 *  2. Purge expired rate-limit buckets.
 *  3. Purge expired password-reset tokens.
 *  4. Purge expired remember-me tokens.
 *  5. Delete orphaned upload files (not referenced in transaction_attachments).
 *  6. Trim audit_logs older than 1 year (keep last 50 000 rows if exceeded).
 *
 * cPanel example:
 *   0 2 * * * /usr/bin/php /home/USER/khfinam/cron/cleanup.php
 */

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\AuditLogger;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$pdo  = Database::pdo();
$log  = [];

// -----------------------------------------------------------------------
// 1. Hard-delete soft-deleted transactions older than 90 days
// -----------------------------------------------------------------------
$stmt = $pdo->prepare('DELETE FROM transactions WHERE deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
$stmt->execute();
$deleted = $stmt->rowCount();
$log[] = "Hard-deleted $deleted old soft-deleted transaction(s).";

// -----------------------------------------------------------------------
// 2. Purge expired rate-limit buckets
// -----------------------------------------------------------------------
try {
    $stmt = $pdo->prepare('DELETE FROM rate_limit_buckets WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    $stmt->execute();
    $log[] = 'Purged ' . $stmt->rowCount() . ' expired rate-limit bucket(s).';
} catch (\Throwable $e) {
    $log[] = 'rate_limit_buckets skip: ' . $e->getMessage();
}

// -----------------------------------------------------------------------
// 3. Purge expired password-reset tokens (> 24 h)
// -----------------------------------------------------------------------
try {
    $stmt = $pdo->prepare('UPDATE users SET reset_token = NULL, reset_token_expires_at = NULL WHERE reset_token IS NOT NULL AND reset_token_expires_at < NOW()');
    $stmt->execute();
    $log[] = 'Cleared ' . $stmt->rowCount() . ' expired reset token(s).';
} catch (\Throwable $e) {
    $log[] = 'reset_token skip: ' . $e->getMessage();
}

// -----------------------------------------------------------------------
// 4. Purge expired remember-me tokens
// -----------------------------------------------------------------------
try {
    $stmt = $pdo->prepare('UPDATE users SET remember_token = NULL, remember_expires_at = NULL WHERE remember_token IS NOT NULL AND remember_expires_at < NOW()');
    $stmt->execute();
    $log[] = 'Cleared ' . $stmt->rowCount() . ' expired remember-me token(s).';
} catch (\Throwable $e) {
    $log[] = 'remember_token skip: ' . $e->getMessage();
}

// -----------------------------------------------------------------------
// 5. Delete orphaned upload files on disk
// -----------------------------------------------------------------------
$uploadDir = $root . '/public/uploads';
$orphans   = 0;
if (is_dir($uploadDir)) {
    $files = array_diff(scandir($uploadDir) ?: [], ['.', '..', '.htaccess']);
    foreach ($files as $filename) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transaction_attachments WHERE stored_filename = ? LIMIT 1');
        $stmt->execute([$filename]);
        if ((int) $stmt->fetchColumn() === 0) {
            $path = $uploadDir . '/' . $filename;
            if (is_file($path)) {
                @unlink($path);
                $orphans++;
            }
        }
    }
}
$log[] = "Removed $orphans orphaned upload file(s).";

// -----------------------------------------------------------------------
// 6. Trim audit_logs — keep last 50 000, delete anything beyond that
// -----------------------------------------------------------------------
try {
    $total = (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    $keep  = 50000;
    if ($total > $keep) {
        $excess = $total - $keep;
        $stmt = $pdo->prepare('DELETE FROM audit_logs ORDER BY id ASC LIMIT ' . $excess);
        $stmt->execute();
        $log[] = "Trimmed $excess old audit_log row(s) (kept $keep).";
    } else {
        $log[] = "audit_logs: $total rows, within limit.";
    }
} catch (\Throwable $e) {
    $log[] = 'audit_logs trim skip: ' . $e->getMessage();
}

// -----------------------------------------------------------------------
// Summary
// -----------------------------------------------------------------------
foreach ($log as $line) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n";
}

AuditLogger::log('cron_cleanup', null, 'system', 'cleanup', ['summary' => $log]);
