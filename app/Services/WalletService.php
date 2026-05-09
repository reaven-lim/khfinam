<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\WalletRepository;

final class WalletService
{
    public function __construct(
        private readonly WalletRepository $wallets = new WalletRepository()
    ) {
    }

    /**
     * Transfer between two wallets (same user). Creates paired internal transactions.
     *
     * @return array{0: int, 1: int} Both transaction IDs
     */
    public function transfer(int $userId, int $fromWalletId, int $toWalletId, float $amount, string $date, ?string $notes = null): array
    {
        if ($fromWalletId === $toWalletId) {
            throw new \InvalidArgumentException('Choose two different wallets.');
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $g = $this->randomUuid();

            $catOut = $this->categoryIdBySlug($pdo, 'transfer-out', $userId);
            $catIn = $this->categoryIdBySlug($pdo, 'transfer-in', $userId);

            $wFrom = $this->requireWallet($pdo, $fromWalletId, $userId);
            $wTo = $this->requireWallet($pdo, $toWalletId, $userId);
            if ((int) $wFrom['currency_id'] !== (int) $wTo['currency_id']) {
                throw new \InvalidArgumentException('Transfers require both wallets to use the same currency (or convert separately).');
            }

            $cid = (int) $wFrom['currency_id'];
            $rateFrom = $this->effectiveRate($pdo, $cid);
            $rateTo = $rateFrom;
            $baseFrom = round($amount * $rateFrom, 4);
            $baseTo = $baseFrom;

            $tid1 = $this->insertTx(
                $pdo,
                $userId,
                $fromWalletId,
                $catOut,
                'expense',
                'Transfer to ' . $wTo['name'],
                $amount,
                $baseFrom,
                $cid,
                $rateFrom,
                $date,
                $notes,
                1,
                $g
            );
            $tid2 = $this->insertTx(
                $pdo,
                $userId,
                $toWalletId,
                $catIn,
                'income',
                'Transfer from ' . $wFrom['name'],
                $amount,
                $baseTo,
                $cid,
                $rateTo,
                $date,
                $notes,
                1,
                $g
            );

            $pdo->commit();

            AuditLogger::log('wallet_transfer', $userId, 'transaction', (string) $tid1, ['to_tx' => $tid2, 'group' => $g]);

            return [$tid1, $tid2];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<string, mixed> $data */
    public function createWallet(int $userId, array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO wallets (user_id, name, wallet_type, currency_id, opening_balance, min_balance_threshold, is_default, is_active, notes, sort_order)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $userId,
            $data['name'],
            $data['wallet_type'] ?? 'cash',
            (int) $data['currency_id'],
            (float) ($data['opening_balance'] ?? 0),
            isset($data['min_balance_threshold']) && $data['min_balance_threshold'] !== '' ? (float) $data['min_balance_threshold'] : null,
            ! empty($data['is_default']) ? 1 : 0,
            isset($data['is_active']) ? (int) ! empty($data['is_active']) : 1,
            $data['notes'] ?? null,
            (int) ($data['sort_order'] ?? 0),
        ]);
        $id = (int) $pdo->lastInsertId();
        if (! empty($data['is_default'])) {
            $pdo->prepare('UPDATE wallets SET is_default = 0 WHERE user_id = ? AND id <> ?')->execute([$userId, $id]);
            $pdo->prepare('UPDATE wallets SET is_default = 1 WHERE id = ?')->execute([$id]);
        }

        return $id;
    }

    /** @param array<string, mixed> $data */
    public function updateWallet(int $userId, int $walletId, array $data): void
    {
        $pdo = Database::pdo();
        $chk = $pdo->prepare('SELECT id FROM wallets WHERE id = ? AND user_id = ? LIMIT 1');
        $chk->execute([$walletId, $userId]);
        if (! $chk->fetchColumn()) {
            throw new \InvalidArgumentException('Wallet not found.');
        }
        $pdo->prepare(
            'UPDATE wallets SET name = ?, wallet_type = ?, currency_id = ?, opening_balance = ?, min_balance_threshold = ?, is_default = ?, is_active = ?, notes = ?, sort_order = ?, updated_at = NOW() WHERE id = ? AND user_id = ?'
        )->execute([
            $data['name'],
            $data['wallet_type'] ?? 'cash',
            (int) $data['currency_id'],
            (float) ($data['opening_balance'] ?? 0),
            isset($data['min_balance_threshold']) && $data['min_balance_threshold'] !== '' ? (float) $data['min_balance_threshold'] : null,
            ! empty($data['is_default']) ? 1 : 0,
            isset($data['is_active']) ? (int) ! empty($data['is_active']) : 1,
            $data['notes'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            $walletId,
            $userId,
        ]);
        if (! empty($data['is_default'])) {
            $pdo->prepare('UPDATE wallets SET is_default = 0 WHERE user_id = ? AND id <> ?')->execute([$userId, $walletId]);
        }
    }

    /**
     * @return array<int, array{wallet_id:int, balance_base:float, name:string, min:?float, below:bool}>
     */
    public function walletBalancesForUser(int $userId): array
    {
        $pdo = Database::pdo();
        $base = (int) ($pdo->query('SELECT id FROM currencies WHERE is_base = 1 LIMIT 1')->fetchColumn() ?: 1);

        $list = $this->wallets->forUser($userId, false);
        $out = [];
        foreach ($list as $w) {
            $wid = (int) $w['id'];
            $curId = (int) $w['currency_id'];
            $rate = $this->effectiveRate($pdo, $curId);
            $openingBase = round((float) $w['opening_balance'] * $rate, 4);

            $q = $pdo->prepare(
                "SELECT COALESCE(SUM(
                    CASE WHEN type = 'income' THEN amount_base
                         WHEN type = 'expense' THEN -amount_base
                         ELSE 0 END
                ), 0) AS flow
                FROM transactions WHERE wallet_id = ? AND user_id = ? AND deleted_at IS NULL"
            );
            $q->execute([$wid, $userId]);
            $flow = (float) $q->fetchColumn();
            $balance = $openingBase + $flow;

            $min = $w['min_balance_threshold'] !== null ? (float) $w['min_balance_threshold'] * $rate : null;
            $below = $min !== null && $balance < $min;

            $out[] = [
                'wallet_id' => $wid,
                'balance_base' => $balance,
                'name' => (string) $w['name'],
                'min' => $min,
                'below' => $below,
            ];
        }

        return $out;
    }

    private function randomUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /** @return array<string, mixed> */
    private function requireWallet(\PDO $pdo, int $id, int $userId): array
    {
        $s = $pdo->prepare('SELECT * FROM wallets WHERE id = ? AND user_id = ? AND is_active = 1 LIMIT 1');
        $s->execute([$id, $userId]);
        $w = $s->fetch(\PDO::FETCH_ASSOC);
        if (! $w) {
            throw new \InvalidArgumentException('Invalid wallet.');
        }

        return $w;
    }

    private function categoryIdBySlug(\PDO $pdo, string $slug, int $userId): int
    {
        $s = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND (user_id IS NULL OR user_id = ?) ORDER BY is_system DESC LIMIT 1');
        $s->execute([$slug, $userId]);
        $id = $s->fetchColumn();
        if (! $id) {
            throw new \RuntimeException('Transfer categories missing. Run migration 002.');
        }

        return (int) $id;
    }

    private function effectiveRate(\PDO $pdo, int $currencyId): float
    {
        $base = (int) ($pdo->query('SELECT id FROM currencies WHERE is_base = 1 LIMIT 1')->fetchColumn() ?: 1);
        if ($currencyId === $base) {
            return 1.0;
        }
        $r = $pdo->prepare(
            'SELECT rate FROM exchange_rates WHERE from_currency_id = ? AND to_currency_id = ? ORDER BY effective_date DESC LIMIT 1'
        );
        $r->execute([$currencyId, $base]);
        $row = $r->fetch(\PDO::FETCH_ASSOC);

        return $row ? (float) $row['rate'] : 1.0;
    }

    private function insertTx(
        \PDO $pdo,
        int $userId,
        int $walletId,
        int $categoryId,
        string $type,
        string $title,
        float $amount,
        float $amountBase,
        int $currencyId,
        float $rate,
        string $date,
        ?string $notes,
        int $isInternal,
        string $group
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO transactions (user_id, wallet_id, category_id, type, title, amount, amount_base, currency_id, exchange_rate_to_base, notes, transaction_date, created_by, is_consolidated_parent, is_internal_transfer, transfer_group)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?,?)'
        );
        $stmt->execute([
            $userId,
            $walletId,
            $categoryId,
            $type,
            $title,
            $amount,
            $amountBase,
            $currencyId,
            $rate,
            $notes,
            $date,
            $userId,
            $isInternal,
            $group,
        ]);

        return (int) $pdo->lastInsertId();
    }
}
