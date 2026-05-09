<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class TransactionService
{
    /**
     * @param array{
     *   type:string,title:string,amount:float,wallet_id:int,category_id:int,transaction_date:string,notes?:string,
     *   parent_transaction_id?:int|null,is_consolidated_parent?:bool,tags?:array<int,string>
     * } $data
     */
    public function createForUser(int $userId, array $data): int
    {
        if (strtolower((string) ($data['type'] ?? '')) === 'transfer') {
            throw new \InvalidArgumentException('Use the transfer form to move money between wallets.');
        }
        $pdo = Database::pdo();
        $parentId = isset($data['parent_transaction_id']) ? (int) $data['parent_transaction_id'] : null;
        $row = $this->buildInsertRow($userId, $data, $parentId);

        if ($parentId) {
            $repo = new \App\Repositories\TransactionRepository();
            $sum = $repo->sumChildrenBase($parentId, $userId);
            $pst = $pdo->prepare('SELECT amount_base FROM transactions WHERE id = ? AND user_id = ? AND deleted_at IS NULL LIMIT 1');
            $pst->execute([$parentId, $userId]);
            $pBase = (float) ($pst->fetchColumn() ?: 0);
            if ($sum + (float) $row['amount_base'] > $pBase + 1e-4) {
                throw new \InvalidArgumentException('Total of child records cannot exceed the parent amount.');
            }
        }

        $pdo->beginTransaction();
        try {
            $id = $this->insertRow($pdo, $row);

            if (! empty($data['is_consolidated_parent'])) {
                $pdo->prepare('UPDATE transactions SET is_consolidated_parent = 1 WHERE id = ?')->execute([$id]);
            }
            if ($parentId) {
                $pdo->prepare('UPDATE transactions SET is_consolidated_parent = 1 WHERE id = ?')->execute([$parentId]);
            }

            if (! empty($data['tags']) && is_array($data['tags'])) {
                $this->syncTags($pdo, $id, $data['tags']);
            }

            $pdo->commit();
            AuditLogger::log('transaction_create', $userId, 'transaction', (string) $id);

            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Native wallet-to-wallet move: one row, type `transfer`. Does not affect income/expense KPIs.
     *
     * @param array{title?:string,amount:float,from_wallet_id:int,to_wallet_id:int,transaction_date:string,notes?:?string,tags?:array<int,string>} $data
     */
    public function createTransferForUser(int $userId, array $data): int
    {
        $pdo = Database::pdo();
        $fromId = (int) ($data['from_wallet_id'] ?? 0);
        $toId = (int) ($data['to_wallet_id'] ?? 0);
        if ($fromId <= 0 || $toId <= 0 || $fromId === $toId) {
            throw new \InvalidArgumentException('Choose two different wallets for the transfer.');
        }
        $amount = round((float) ($data['amount'] ?? 0), 4);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $date = (string) ($data['transaction_date'] ?? date('Y-m-d'));
        $wStmt = $pdo->prepare('SELECT * FROM wallets WHERE id = ? AND user_id = ? AND is_active = 1 LIMIT 1');
        $wStmt->execute([$fromId, $userId]);
        $wFrom = $wStmt->fetch(PDO::FETCH_ASSOC);
        $wStmt->execute([$toId, $userId]);
        $wTo = $wStmt->fetch(PDO::FETCH_ASSOC);
        if (! $wFrom || ! $wTo) {
            throw new \InvalidArgumentException('Invalid wallet.');
        }
        if ((int) $wFrom['currency_id'] !== (int) $wTo['currency_id']) {
            throw new \InvalidArgumentException('Both wallets must use the same currency for a transfer.');
        }
        $info = $this->resolveAmountBaseFromWallet($pdo, $userId, $fromId, $amount, $date);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = 'Transfer · ' . (string) $wFrom['name'] . ' → ' . (string) $wTo['name'];
        }
        $row = [
            'user_id' => $userId,
            'wallet_id' => null,
            'from_wallet_id' => $fromId,
            'to_wallet_id' => $toId,
            'category_id' => null,
            'parent_transaction_id' => null,
            'type' => 'transfer',
            'title' => $title,
            'amount' => $amount,
            'amount_base' => $info['amount_base'],
            'currency_id' => $info['currency_id'],
            'exchange_rate_to_base' => $info['rate'],
            'notes' => array_key_exists('notes', $data) ? (($data['notes'] !== null && (string) $data['notes'] !== '') ? (string) $data['notes'] : null) : null,
            'transaction_date' => $date,
            'created_by' => $userId,
            'recurring_schedule_id' => null,
            'is_consolidated_parent' => 0,
            'is_internal_transfer' => 0,
            'transfer_group' => null,
        ];
        $pdo->beginTransaction();
        try {
            $id = $this->insertRow($pdo, $row);
            if (! empty($data['tags']) && is_array($data['tags'])) {
                $this->syncTags($pdo, $id, $data['tags']);
            }
            $pdo->commit();
            AuditLogger::log('transaction_transfer', $userId, 'transaction', (string) $id);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateForUser(int $userId, int $transactionId, array $data): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
        $cur = $this->loadTransaction($pdo, $transactionId, $userId);
        if (! $cur) {
            $pdo->rollBack();
            throw new \InvalidArgumentException('Transaction not found.');
        }
        if (($cur['type'] ?? '') === 'transfer') {
            $this->updateTransferForUserInTransaction($pdo, $userId, $transactionId, $cur, $data);
            AuditLogger::log('transaction_update', $userId, 'transaction', (string) $transactionId);
            $pdo->commit();

            return;
        }

        $newType = isset($data['type']) ? ((string) $data['type'] === 'income' ? 'income' : 'expense') : (string) $cur['type'];
        $newTitle = isset($data['title']) ? (string) $data['title'] : (string) $cur['title'];
        $newAmount = isset($data['amount']) ? (float) $data['amount'] : (float) $cur['amount'];
        $newWallet = isset($data['wallet_id']) ? (int) $data['wallet_id'] : (int) $cur['wallet_id'];
        $newCat = isset($data['category_id']) ? (int) $data['category_id'] : (int) $cur['category_id'];
        $newDate = isset($data['transaction_date']) ? (string) $data['transaction_date'] : (string) $cur['transaction_date'];
        $newNotes = array_key_exists('notes', $data) ? ($data['notes'] !== null ? (string) $data['notes'] : null) : ($cur['notes'] !== null ? (string) $cur['notes'] : null);

        if ($newAmount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }

        $rateInfo = $this->resolveAmountBase($pdo, $userId, $newWallet, $newCat, $newType, $newAmount, $newDate);

        if (! empty($cur['parent_transaction_id'])) {
            $sumSiblings = (new \App\Repositories\TransactionRepository())->sumChildrenBase((int) $cur['parent_transaction_id'], $userId)
                - (float) $cur['amount_base'];
            $parent = $this->loadTransaction($pdo, (int) $cur['parent_transaction_id'], $userId);
            if ($parent && $sumSiblings + $rateInfo['amount_base'] > (float) $parent['amount_base'] + 1e-4) {
                $pdo->rollBack();
                throw new \InvalidArgumentException('Child amounts cannot exceed the parent envelope amount.');
            }
        }

        if (! empty($cur['is_consolidated_parent'])) {
            $childrenSum = (new \App\Repositories\TransactionRepository())->sumChildrenBase($transactionId, $userId);
            if ($childrenSum > $rateInfo['amount_base'] + 1e-4) {
                $pdo->rollBack();
                throw new \InvalidArgumentException('Parent amount cannot be less than the sum of child records.');
            }
        }

        $changes = [
            'before' => [
                'title' => $cur['title'],
                'amount' => $cur['amount'],
                'wallet_id' => $cur['wallet_id'],
                'category_id' => $cur['category_id'],
                'transaction_date' => $cur['transaction_date'],
            ],
            'after' => [
                'title' => $newTitle,
                'amount' => $newAmount,
                'wallet_id' => $newWallet,
                'category_id' => $newCat,
                'transaction_date' => $newDate,
            ],
        ];

        $pdo->prepare(
            'UPDATE transactions SET type = ?, title = ?, amount = ?, amount_base = ?, wallet_id = ?, category_id = ?, currency_id = ?, exchange_rate_to_base = ?, notes = ?, transaction_date = ?, updated_at = NOW() WHERE id = ? AND user_id = ?'
        )->execute(
            [
                $newType,
                $newTitle,
                $newAmount,
                $rateInfo['amount_base'],
                $newWallet,
                $newCat,
                $rateInfo['currency_id'],
                $rateInfo['rate'],
                $newNotes,
                $newDate,
                $transactionId,
                $userId,
            ]
        );

        $pdo->prepare(
            'INSERT INTO transaction_edit_history (transaction_id, user_id, changes_json) VALUES (?,?,?)'
        )->execute([
            $transactionId,
            $userId,
            json_encode($changes, JSON_THROW_ON_ERROR),
        ]);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $pdo->prepare('DELETE FROM transaction_tags WHERE transaction_id = ?')->execute([$transactionId]);
            $this->syncTags($pdo, $transactionId, $data['tags']);
        }

        AuditLogger::log('transaction_update', $userId, 'transaction', (string) $transactionId);
        $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function softDeleteForUser(int $userId, int $transactionId): void
    {
        $pdo = Database::pdo();
        $cur = $this->loadTransaction($pdo, $transactionId, $userId);
        if (! $cur) {
            throw new \InvalidArgumentException('Transaction not found.');
        }
        if (! empty($cur['is_consolidated_parent'])) {
            $pdo->prepare('UPDATE transactions SET deleted_at = NOW() WHERE parent_transaction_id = ? AND user_id = ?')->execute([$transactionId, $userId]);
        }
        $pdo->prepare('UPDATE transactions SET deleted_at = NOW() WHERE id = ? AND user_id = ?')->execute([$transactionId, $userId]);
        AuditLogger::log('transaction_delete', $userId, 'transaction', (string) $transactionId);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{amount_base: float, currency_id: int, rate: float}
     */
    private function resolveAmountBase(PDO $pdo, int $userId, int $walletId, int $categoryId, string $type, float $amount, string $date): array
    {
        $wStmt = $pdo->prepare('SELECT w.* FROM wallets w WHERE w.id = ? AND w.user_id = ? LIMIT 1');
        $wStmt->execute([$walletId, $userId]);
        $wallet = $wStmt->fetch(PDO::FETCH_ASSOC);
        if (! $wallet) {
            throw new \InvalidArgumentException('Invalid wallet.');
        }
        $cStmt = $pdo->prepare('SELECT id, type FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?) LIMIT 1');
        $cStmt->execute([$categoryId, $userId]);
        $cat = $cStmt->fetch(PDO::FETCH_ASSOC);
        if (! $cat || $cat['type'] !== $type) {
            throw new \InvalidArgumentException('Category does not match transaction type.');
        }
        $currencyId = (int) $wallet['currency_id'];
        $base = $pdo->query('SELECT id FROM currencies WHERE is_base = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $baseId = (int) ($base['id'] ?? 1);
        $rate = 1.0;
        if ($currencyId !== $baseId) {
            $r = $pdo->prepare(
                'SELECT rate FROM exchange_rates WHERE from_currency_id = ? AND to_currency_id = ? ORDER BY effective_date DESC LIMIT 1'
            );
            $r->execute([$currencyId, $baseId]);
            $row = $r->fetch(PDO::FETCH_ASSOC);
            $rate = $row ? (float) $row['rate'] : 1.0;
        }

        return [
            'amount_base' => round($amount * $rate, 4),
            'currency_id' => $currencyId,
            'rate' => $rate,
        ];
    }

    /**
     * Amount in wallet currency → base (no category validation).
     *
     * @return array{amount_base: float, currency_id: int, rate: float}
     */
    private function resolveAmountBaseFromWallet(PDO $pdo, int $userId, int $walletId, float $amount, string $date): array
    {
        $wStmt = $pdo->prepare('SELECT * FROM wallets WHERE id = ? AND user_id = ? LIMIT 1');
        $wStmt->execute([$walletId, $userId]);
        $wallet = $wStmt->fetch(PDO::FETCH_ASSOC);
        if (! $wallet) {
            throw new \InvalidArgumentException('Invalid wallet.');
        }
        $currencyId = (int) $wallet['currency_id'];
        $base = $pdo->query('SELECT id FROM currencies WHERE is_base = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $baseId = (int) ($base['id'] ?? 1);
        $rate = 1.0;
        if ($currencyId !== $baseId) {
            $r = $pdo->prepare(
                'SELECT rate FROM exchange_rates WHERE from_currency_id = ? AND to_currency_id = ? ORDER BY effective_date DESC LIMIT 1'
            );
            $r->execute([$currencyId, $baseId]);
            $row = $r->fetch(PDO::FETCH_ASSOC);
            $rate = $row ? (float) $row['rate'] : 1.0;
        }
        unset($date);

        return [
            'amount_base' => round($amount * $rate, 4),
            'currency_id' => $currencyId,
            'rate' => $rate,
        ];
    }

    /**
     * @param array<string, mixed> $cur
     * @param array<string, mixed> $data
     */
    private function updateTransferForUserInTransaction(PDO $pdo, int $userId, int $transactionId, array $cur, array $data): void
    {
        $newTitle = isset($data['title']) ? (string) $data['title'] : (string) $cur['title'];
        $newAmount = isset($data['amount']) ? (float) $data['amount'] : (float) $cur['amount'];
        $newDate = isset($data['transaction_date']) ? (string) $data['transaction_date'] : (string) $cur['transaction_date'];
        $newNotes = array_key_exists('notes', $data) ? ($data['notes'] !== null ? (string) $data['notes'] : null) : ($cur['notes'] !== null ? (string) $cur['notes'] : null);
        $newFrom = isset($data['from_wallet_id']) ? (int) $data['from_wallet_id'] : (int) ($cur['from_wallet_id'] ?? 0);
        $newTo = isset($data['to_wallet_id']) ? (int) $data['to_wallet_id'] : (int) ($cur['to_wallet_id'] ?? 0);
        if ($newFrom <= 0 || $newTo <= 0 || $newFrom === $newTo) {
            throw new \InvalidArgumentException('Choose two different wallets for the transfer.');
        }
        if ($newAmount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $wStmt = $pdo->prepare('SELECT * FROM wallets WHERE id = ? AND user_id = ? LIMIT 1');
        $wStmt->execute([$newFrom, $userId]);
        $wFrom = $wStmt->fetch(PDO::FETCH_ASSOC);
        $wStmt->execute([$newTo, $userId]);
        $wTo = $wStmt->fetch(PDO::FETCH_ASSOC);
        if (! $wFrom || ! $wTo) {
            throw new \InvalidArgumentException('Invalid wallet.');
        }
        if ((int) $wFrom['currency_id'] !== (int) $wTo['currency_id']) {
            throw new \InvalidArgumentException('Both wallets must use the same currency for a transfer.');
        }
        $rateInfo = $this->resolveAmountBaseFromWallet($pdo, $userId, $newFrom, $newAmount, $newDate);
        $pdo->prepare(
            'UPDATE transactions SET type = ?, title = ?, amount = ?, amount_base = ?, wallet_id = NULL, from_wallet_id = ?, to_wallet_id = ?,
             category_id = NULL, currency_id = ?, exchange_rate_to_base = ?, notes = ?, transaction_date = ?, updated_at = NOW()
             WHERE id = ? AND user_id = ?'
        )->execute([
            'transfer',
            $newTitle,
            $newAmount,
            $rateInfo['amount_base'],
            $newFrom,
            $newTo,
            $rateInfo['currency_id'],
            $rateInfo['rate'],
            $newNotes,
            $newDate,
            $transactionId,
            $userId,
        ]);
        $changes = [
            'before' => [
                'title' => $cur['title'],
                'amount' => $cur['amount'],
                'from_wallet_id' => $cur['from_wallet_id'],
                'to_wallet_id' => $cur['to_wallet_id'],
                'transaction_date' => $cur['transaction_date'],
            ],
            'after' => [
                'title' => $newTitle,
                'amount' => $newAmount,
                'from_wallet_id' => $newFrom,
                'to_wallet_id' => $newTo,
                'transaction_date' => $newDate,
            ],
        ];
        $pdo->prepare(
            'INSERT INTO transaction_edit_history (transaction_id, user_id, changes_json) VALUES (?,?,?)'
        )->execute([
            $transactionId,
            $userId,
            json_encode($changes, JSON_THROW_ON_ERROR),
        ]);
        if (isset($data['tags']) && is_array($data['tags'])) {
            $pdo->prepare('DELETE FROM transaction_tags WHERE transaction_id = ?')->execute([$transactionId]);
            $this->syncTags($pdo, $transactionId, $data['tags']);
        }
    }

    /** @param array<string, mixed> $data */
    private function buildInsertRow(int $userId, array $data, ?int $parentId): array
    {
        $type = $data['type'] === 'income' ? 'income' : 'expense';
        $pdo = Database::pdo();
        $amount = round((float) $data['amount'], 4);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $info = $this->resolveAmountBase(
            $pdo,
            $userId,
            (int) $data['wallet_id'],
            (int) $data['category_id'],
            $type,
            $amount,
            $data['transaction_date']
        );

        return [
            'user_id' => $userId,
            'wallet_id' => (int) $data['wallet_id'],
            'from_wallet_id' => null,
            'to_wallet_id' => null,
            'category_id' => (int) $data['category_id'],
            'parent_transaction_id' => $parentId,
            'type' => $type,
            'title' => (string) $data['title'],
            'amount' => $amount,
            'amount_base' => $info['amount_base'],
            'currency_id' => $info['currency_id'],
            'exchange_rate_to_base' => $info['rate'],
            'notes' => $data['notes'] ?? null,
            'transaction_date' => (string) $data['transaction_date'],
            'created_by' => $userId,
            'recurring_schedule_id' => null,
            'is_consolidated_parent' => ! empty($data['is_consolidated_parent']) ? 1 : 0,
            'is_internal_transfer' => ! empty($data['is_internal_transfer']) ? 1 : 0,
            'transfer_group' => $data['transfer_group'] ?? null,
        ];
    }

    /** @param array<string, mixed> $row */
    private function insertRow(PDO $pdo, array $row): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO transactions (user_id, wallet_id, from_wallet_id, to_wallet_id, category_id, parent_transaction_id, type, title, amount, amount_base, currency_id, exchange_rate_to_base, notes, transaction_date, created_by, recurring_schedule_id, is_consolidated_parent, is_internal_transfer, transfer_group)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $row['user_id'],
            $row['wallet_id'],
            $row['from_wallet_id'],
            $row['to_wallet_id'],
            $row['category_id'],
            $row['parent_transaction_id'],
            $row['type'],
            $row['title'],
            $row['amount'],
            $row['amount_base'],
            $row['currency_id'],
            $row['exchange_rate_to_base'],
            $row['notes'],
            $row['transaction_date'],
            $row['created_by'],
            $row['recurring_schedule_id'],
            $row['is_consolidated_parent'],
            $row['is_internal_transfer'],
            $row['transfer_group'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    private function loadTransaction(PDO $pdo, int $id, int $userId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND user_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<int, string> $tags */
    private function syncTags(PDO $pdo, int $transactionId, array $tags): void
    {
        $ins = $pdo->prepare('INSERT INTO transaction_tags (transaction_id, tag) VALUES (?,?)');
        foreach (array_unique(array_filter(array_map('trim', $tags))) as $tag) {
            if ($tag === '') {
                continue;
            }
            $ins->execute([$transactionId, mb_substr($tag, 0, 64)]);
        }
    }
}
