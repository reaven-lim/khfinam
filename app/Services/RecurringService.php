<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use PDO;

final class RecurringService
{
    /**
     * @param array{wallet_id:int,category_id:int,type:string,title:string,amount:float,currency_id:int,frequency:string,interval_value?:int,start_date:string,end_date?:?string,notes?:?string} $data
     */
    public function createSchedule(int $userId, array $data): int
    {
        $pdo = Database::pdo();
        $wid = (int) $data['wallet_id'];
        $chk = $pdo->prepare('SELECT id, currency_id FROM wallets WHERE id = ? AND user_id = ? AND is_active = 1 LIMIT 1');
        $chk->execute([$wid, $userId]);
        $w = $chk->fetch(PDO::FETCH_ASSOC);
        if (! $w) {
            throw new \InvalidArgumentException('Wallet not found.');
        }
        $catId = (int) $data['category_id'];
        $type = ($data['type'] ?? '') === 'income' ? 'income' : 'expense';
        $cq = $pdo->prepare('SELECT id, type FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?) LIMIT 1');
        $cq->execute([$catId, $userId]);
        $cat = $cq->fetch(PDO::FETCH_ASSOC);
        if (! $cat || (string) $cat['type'] !== $type) {
            throw new \InvalidArgumentException('Category does not match transaction type.');
        }
        $freq = (string) ($data['frequency'] ?? 'monthly');
        if (! in_array($freq, ['daily', 'weekly', 'monthly', 'yearly', 'custom'], true)) {
            $freq = 'monthly';
        }
        $interval = max(1, (int) ($data['interval_value'] ?? 1));
        $start = (string) $data['start_date'];
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            throw new \InvalidArgumentException('Invalid start date.');
        }
        $end = isset($data['end_date']) && $data['end_date'] !== '' && $data['end_date'] !== null
            ? (string) $data['end_date'] : null;
        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Title required.');
        }
        $cid = (int) $data['currency_id'];
        if ((int) $w['currency_id'] !== $cid) {
            throw new \InvalidArgumentException('Currency must match the wallet currency.');
        }
        $pdo->prepare(
            'INSERT INTO recurring_schedules (user_id, wallet_id, category_id, type, title, amount, currency_id, frequency, interval_value, start_date, end_date, next_occurrence, is_paused, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?)'
        )->execute([
            $userId,
            $wid,
            $catId,
            $type,
            $title,
            $amount,
            $cid,
            $freq,
            $interval,
            $start,
            $end,
            $start,
            $data['notes'] ?? null,
        ]);
        $id = (int) $pdo->lastInsertId();
        AuditLogger::log('recurring_create', $userId, 'recurring_schedule', (string) $id);

        return $id;
    }

    public function pause(int $userId, int $scheduleId, bool $paused): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE recurring_schedules SET is_paused = ? WHERE id = ? AND user_id = ?')->execute([$paused ? 1 : 0, $scheduleId, $userId]);
        AuditLogger::log('recurring_pause', $userId, 'recurring_schedule', (string) $scheduleId, ['paused' => $paused]);
    }

    public function skipNext(int $userId, int $scheduleId): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE recurring_schedules SET skip_next = 1 WHERE id = ? AND user_id = ?')->execute([$scheduleId, $userId]);
        AuditLogger::log('recurring_skip', $userId, 'recurring_schedule', (string) $scheduleId);
    }

    /**
     * @return array<int, string> ISO dates
     */
    public function previewOccurrences(array $row, int $count = 6): array
    {
        $next = new DateTimeImmutable((string) $row['next_occurrence']);
        $freq = (string) $row['frequency'];
        $interval = max(1, (int) $row['interval_value']);
        $out = [];
        $cur = $next;
        for ($i = 0; $i < $count; $i++) {
            $out[] = $cur->format('Y-m-d');
            $cur = $this->advance($cur, $freq, $interval);
        }

        return $out;
    }

    /**
     * Generate one occurrence now for a schedule (same logic as cron).
     */
    public function runOne(int $userId, int $scheduleId): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM recurring_schedules WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$scheduleId, $userId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $r) {
            throw new \InvalidArgumentException('Schedule not found.');
        }
        if (! empty($r['is_paused'])) {
            throw new \InvalidArgumentException('Schedule is paused.');
        }

        $uid = (int) $r['user_id'];
        $walletId = (int) $r['wallet_id'];
        $catId = (int) $r['category_id'];
        $type = $r['type'] === 'income' ? 'income' : 'expense';
        $amount = (float) $r['amount'];
        $currencyId = (int) $r['currency_id'];

        $rate = 1.0;
        $baseId = (int) ($pdo->query('SELECT id FROM currencies WHERE is_base = 1 LIMIT 1')->fetchColumn() ?: 1);
        if ($currencyId !== $baseId) {
            $q = $pdo->prepare(
                'SELECT rate FROM exchange_rates WHERE from_currency_id = ? AND to_currency_id = ? ORDER BY effective_date DESC LIMIT 1'
            );
            $q->execute([$currencyId, $baseId]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            $rate = $row ? (float) $row['rate'] : 1.0;
        }
        $amountBase = round($amount * $rate, 4);
        $date = (string) $r['next_occurrence'];

        $pdo->prepare(
            'INSERT INTO transactions (user_id, wallet_id, from_wallet_id, to_wallet_id, category_id, parent_transaction_id, type, title, amount, amount_base, currency_id, exchange_rate_to_base, notes, transaction_date, created_by, recurring_schedule_id, is_consolidated_parent, is_internal_transfer, transfer_group)
             VALUES (?,?,NULL,NULL,?,NULL,?,?,?,?,?,NULL,?,?,?,?,0,0,NULL)'
        )->execute([
            $uid,
            $walletId,
            $catId,
            $type,
            $r['title'],
            $amount,
            $amountBase,
            $currencyId,
            $rate,
            $date,
            $uid,
            $scheduleId,
        ]);

        $next = new DateTimeImmutable($date);
        $freq = (string) $r['frequency'];
        $interval = max(1, (int) $r['interval_value']);
        $nextDate = $this->advance($next, $freq, $interval);

        $pdo->prepare('UPDATE recurring_schedules SET last_generated_at = NOW(), next_occurrence = ? WHERE id = ?')
            ->execute([$nextDate->format('Y-m-d'), $scheduleId]);

        AuditLogger::log('recurring_manual', $userId, 'recurring_schedule', (string) $scheduleId);
    }

    private function advance(DateTimeImmutable $d, string $freq, int $interval): DateTimeImmutable
    {
        return match ($freq) {
            'daily' => $d->modify('+' . $interval . ' day'),
            'weekly' => $d->modify('+' . (7 * $interval) . ' day'),
            'monthly' => $d->modify('+' . $interval . ' month'),
            'yearly' => $d->modify('+' . $interval . ' year'),
            'custom' => $d->modify('+' . $interval . ' day'),
            default => $d->modify('+' . $interval . ' day'),
        };
    }
}
