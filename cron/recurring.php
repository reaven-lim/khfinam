<?php

declare(strict_types=1);

/**
 * CLI: php cron/recurring.php
 * Crontab (example): 0 * * * * cd /path/to/khfinam && php cron/recurring.php
 */

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\AuditLogger;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$pdo = Database::pdo();
$today = (new DateTimeImmutable('today'))->format('Y-m-d');

$stmt = $pdo->prepare(
    'SELECT * FROM recurring_schedules WHERE is_paused = 0 AND next_occurrence <= ? AND (end_date IS NULL OR end_date >= ?)'
);
$stmt->execute([$today, $today]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    if (! empty($r['skip_next'])) {
        $next = new DateTimeImmutable((string) $r['next_occurrence']);
        $freq = (string) $r['frequency'];
        $interval = max(1, (int) $r['interval_value']);
        $nextDate = match ($freq) {
            'daily' => $next->modify('+' . $interval . ' day'),
            'weekly' => $next->modify('+' . (7 * $interval) . ' day'),
            'monthly' => $next->modify('+' . $interval . ' month'),
            'yearly' => $next->modify('+' . $interval . ' year'),
            'custom' => $next->modify('+' . $interval . ' day'),
            default => $next->modify('+' . $interval . ' month'),
        };
        $pdo->prepare('UPDATE recurring_schedules SET skip_next = 0, next_occurrence = ? WHERE id = ?')
            ->execute([$nextDate->format('Y-m-d'), (int) $r['id']]);
        continue;
    }

    $uid = (int) $r['user_id'];
    $walletId = (int) $r['wallet_id'];
    $catId = (int) $r['category_id'];
    $type = $r['type'] === 'income' ? 'income' : 'expense';
    $amount = (float) $r['amount'];
    $currencyId = (int) $r['currency_id'];
    $scheduleId = (int) $r['id'];

    $rate = 1.0;
    $base = $pdo->query('SELECT id FROM currencies WHERE is_base = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $baseId = (int) ($base['id'] ?? 1);
    if ($currencyId !== $baseId) {
        $q = $pdo->prepare(
            'SELECT rate FROM exchange_rates WHERE from_currency_id = ? AND to_currency_id = ? ORDER BY effective_date DESC LIMIT 1'
        );
        $q->execute([$currencyId, $baseId]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $rate = $row ? (float) $row['rate'] : 1.0;
    }
    $amountBase = round($amount * $rate, 4);

    $ins = $pdo->prepare(
        'INSERT INTO transactions (user_id, wallet_id, category_id, type, title, amount, amount_base, currency_id, exchange_rate_to_base, transaction_date, created_by, recurring_schedule_id, is_consolidated_parent, is_internal_transfer, transfer_group)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,0,NULL)'
    );
    $ins->execute([
        $uid,
        $walletId,
        $catId,
        $type,
        $r['title'],
        $amount,
        $amountBase,
        $currencyId,
        $rate,
        $r['next_occurrence'],
        $uid,
        $scheduleId,
    ]);

    $next = new DateTimeImmutable((string) $r['next_occurrence']);
    $freq = (string) $r['frequency'];
    $interval = max(1, (int) $r['interval_value']);
    $nextDate = match ($freq) {
        'daily' => $next->modify('+' . $interval . ' day'),
        'weekly' => $next->modify('+' . (7 * $interval) . ' day'),
        'monthly' => $next->modify('+' . $interval . ' month'),
        'yearly' => $next->modify('+' . $interval . ' year'),
        'custom' => $next->modify('+' . $interval . ' day'),
        default => $next->modify('+' . $interval . ' month'),
    };

    $pdo->prepare('UPDATE recurring_schedules SET last_generated_at = NOW(), next_occurrence = ? WHERE id = ?')
        ->execute([$nextDate->format('Y-m-d'), $scheduleId]);

    AuditLogger::log('recurring_generated', $uid, 'recurring_schedule', (string) $scheduleId, ['transaction_date' => $r['next_occurrence']]);
}

echo "Processed " . count($rows) . " recurring schedule(s).\n";
