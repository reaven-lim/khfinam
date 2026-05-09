<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTypeRepository;

final class WalletService
{
    public function __construct(
        private readonly WalletRepository $wallets = new WalletRepository(),
        private readonly WalletTypeRepository $walletTypes = new WalletTypeRepository()
    ) {
    }

    /**
     * Transfer between two wallets (same user). Inserts one `transfer` row (excluded from income and expense totals).
     *
     * @return array{0: int, 1: int} Duplicate ids for callers that historically expected paired rows
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
        $wFrom = $this->requireWallet($pdo, $fromWalletId, $userId);
        $wTo = $this->requireWallet($pdo, $toWalletId, $userId);
        if ((int) $wFrom['currency_id'] !== (int) $wTo['currency_id']) {
            throw new \InvalidArgumentException('Transfers require both wallets to use the same currency (or convert separately).');
        }
        $title = 'Transfer · ' . $wFrom['name'] . ' → ' . $wTo['name'];
        $id = (new TransactionService())->createTransferForUser($userId, [
            'title' => $title,
            'amount' => $amount,
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id' => $toWalletId,
            'transaction_date' => $date,
            'notes' => $notes,
            'tags' => [],
        ]);

        return [$id, $id];
    }

    /**
     * @param array<string, mixed> $data expects wallet_type_id (preferred) or legacy wallet_type slug
     */
    public function createWallet(int $ownerUserId, array $data, bool $allowInactiveWalletType = false): int
    {
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new \InvalidArgumentException('Wallet name is required.');
        }
        $typeId = $this->resolveWalletTypeId($data, ! $allowInactiveWalletType);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO wallets (user_id, name, wallet_type_id, currency_id, opening_balance, min_balance_threshold, is_default, is_active, notes, sort_order)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $ownerUserId,
            trim((string) $data['name']),
            $typeId,
            (int) $data['currency_id'],
            (float) ($data['opening_balance'] ?? 0),
            isset($data['min_balance_threshold']) && $data['min_balance_threshold'] !== '' ? (float) $data['min_balance_threshold'] : null,
            ! empty($data['is_default']) ? 1 : 0,
            isset($data['is_active']) ? (int) ! empty($data['is_active']) : 1,
            isset($data['notes']) ? trim((string) $data['notes']) ?: null : null,
            (int) ($data['sort_order'] ?? 0),
        ]);
        $id = (int) $pdo->lastInsertId();
        if (! empty($data['is_default'])) {
            $pdo->prepare('UPDATE wallets SET is_default = 0 WHERE user_id = ? AND id <> ?')->execute([$ownerUserId, $id]);
            $pdo->prepare('UPDATE wallets SET is_default = 1 WHERE id = ?')->execute([$id]);
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateWalletForOwner(int $ownerUserId, int $walletId, array $data, bool $allowInactiveWalletType = false): void
    {
        $pdo = Database::pdo();
        $chk = $pdo->prepare('SELECT id FROM wallets WHERE id = ? AND user_id = ? LIMIT 1');
        $chk->execute([$walletId, $ownerUserId]);
        if (! $chk->fetchColumn()) {
            throw new \InvalidArgumentException('Wallet not found.');
        }
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new \InvalidArgumentException('Wallet name is required.');
        }
        $typeId = $this->resolveWalletTypeId($data, ! $allowInactiveWalletType);
        $pdo->prepare(
            'UPDATE wallets SET name = ?, wallet_type_id = ?, currency_id = ?, opening_balance = ?, min_balance_threshold = ?, is_default = ?, is_active = ?, notes = ?, sort_order = ?, updated_at = NOW() WHERE id = ? AND user_id = ?'
        )->execute([
            trim((string) $data['name']),
            $typeId,
            (int) $data['currency_id'],
            (float) ($data['opening_balance'] ?? 0),
            isset($data['min_balance_threshold']) && $data['min_balance_threshold'] !== '' ? (float) $data['min_balance_threshold'] : null,
            ! empty($data['is_default']) ? 1 : 0,
            isset($data['is_active']) ? (int) ! empty($data['is_active']) : 1,
            isset($data['notes']) ? trim((string) $data['notes']) ?: null : null,
            (int) ($data['sort_order'] ?? 0),
            $walletId,
            $ownerUserId,
        ]);
        if (! empty($data['is_default'])) {
            $pdo->prepare('UPDATE wallets SET is_default = 0 WHERE user_id = ? AND id <> ?')->execute([$ownerUserId, $walletId]);
        }
    }

    public function deactivateWalletForOwner(int $ownerUserId, int $walletId): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE wallets SET is_active = 0, updated_at = NOW() WHERE id = ? AND user_id = ?')->execute([$walletId, $ownerUserId]);
    }

    /**
     * Hard-delete only when no transactions and no recurring rules reference this wallet.
     */
    public function deleteWalletForOwner(int $ownerUserId, int $walletId): void
    {
        $repo = new WalletRepository();
        if ($repo->findForOwner($walletId, $ownerUserId) === null) {
            throw new \InvalidArgumentException('Wallet not found.');
        }
        if ($repo->countTransactions($walletId) > 0) {
            throw new \InvalidArgumentException('Wallet has transactions; deactivate instead of delete.');
        }
        if ($repo->countRecurring($walletId) > 0) {
            throw new \InvalidArgumentException('Wallet has recurring schedules; deactivate instead of delete.');
        }
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM wallets WHERE id = ? AND user_id = ?')->execute([$walletId, $ownerUserId]);
    }

    /** @param array<string, mixed> $data */
    private function resolveWalletTypeId(array $data, bool $requireActiveType = true): int
    {
        $tid = isset($data['wallet_type_id']) ? (int) $data['wallet_type_id'] : 0;
        if ($tid > 0) {
            $row = $this->walletTypes->findById($tid);
            if ($row === null || ($requireActiveType && empty($row['is_active']))) {
                throw new \InvalidArgumentException('Invalid or inactive wallet type.');
            }

            return $tid;
        }
        if (! empty($data['wallet_type'])) {
            $row = $this->walletTypes->findBySlug((string) $data['wallet_type']);
            if ($row !== null && (! $requireActiveType || ! empty($row['is_active']))) {
                return (int) $row['id'];
            }
        }
        throw new \InvalidArgumentException('Wallet type is required.');
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
                    CASE
                         WHEN type = 'income' AND wallet_id = ? THEN amount_base
                         WHEN type = 'expense' AND wallet_id = ? THEN -amount_base
                         WHEN type = 'transfer' AND from_wallet_id = ? THEN -amount_base
                         WHEN type = 'transfer' AND to_wallet_id = ? THEN amount_base
                         ELSE 0 END
                ), 0) AS flow
                FROM transactions WHERE user_id = ? AND deleted_at IS NULL"
            );
            $q->execute([$wid, $wid, $wid, $wid, $userId]);
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
}
