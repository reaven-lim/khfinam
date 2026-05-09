<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\TransactionRepository;
use PDO;

/**
 * Shared transaction intelligence listing + KPI/chart payloads for admin (optional user lens)
 * and personal dashboard (always scoped user).
 */
final class TransactionIntelligenceService
{
    public function chartWindow(string $from, string $to): array
    {
        $vf = $from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
        $vt = $to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);
        if ($vf && $vt) {
            return [$from, $to];
        }
        if ($vf && ! $vt) {
            return [$from, date('Y-m-d')];
        }
        if (! $vf && $vt) {
            return [date('Y-m-d', strtotime('-90 days', strtotime($to))), $to];
        }
        $end = date('Y-m-d');

        return [date('Y-m-d', strtotime('-90 days')), $end];
    }

    /**
     * Rows for ledger preview. Pass $scopedUserId = 0 only from super-admin tooling (all users).
     *
     * @return list<array<string, mixed>>
     */
    public function filteredTransactionListing(int $scopedUserId, string $from, string $to, string $type): array
    {
        $pdo = Database::pdo();
        $needUsername = $scopedUserId === 0;

        $sql = 'SELECT t.*, w.name AS wallet_name, c.name AS category_name';
        if ($needUsername) {
            $sql .= ', u.username';
        }
        $sql .= ' FROM transactions t
            JOIN wallets w ON w.id = t.wallet_id
            JOIN categories c ON c.id = t.category_id';
        if ($needUsername) {
            $sql .= ' JOIN users u ON u.id = t.user_id';
        }
        $sql .= ' WHERE t.deleted_at IS NULL';
        $params = [];

        if ($scopedUserId > 0) {
            $sql .= ' AND t.user_id = ?';
            $params[] = $scopedUserId;
        }
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $sql .= ' AND t.transaction_date >= ?';
            $params[] = $from;
        }
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $sql .= ' AND t.transaction_date <= ?';
            $params[] = $to;
        }
        if ($type === 'income' || $type === 'expense') {
            $sql .= ' AND t.type = ?';
            $params[] = $type;
        }
        $sql .= ' ORDER BY t.transaction_date DESC, t.id DESC LIMIT 200';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Analytics payload keyed for admin/transactions (and personal dashboard reuse).
     *
     * @param int $userId 0 = system-wide aggregation (super admin); else single user id
     *
     * @return array<string, mixed>
     */
    public function buildFilteredAnalyticsPayload(int $userId, string $from, string $to, string $type): array
    {
        $repo = new TransactionRepository();
        [$seriesFrom, $seriesTo] = $this->chartWindow($from, $to);
        $summary = $repo->adminFilteredSummary($userId, $from, $to, $type);
        $topCat = $repo->adminFilteredTopCategory($userId, $from, $to, $type);
        $daily = $repo->adminFilteredDailySeries($userId, $from, $to, $type, $seriesFrom, $seriesTo);
        $catBreak = $repo->adminFilteredCategoryBreakdown($userId, $from, $to, $type, $seriesFrom, $seriesTo);
        $walletBreak = $repo->adminFilteredWalletBreakdown($userId, $from, $to, $type, $seriesFrom, $seriesTo);
        $recurRatio = $repo->adminFilteredRecurringRatio($userId, $from, $to, $type);
        $topWallet = $repo->adminFilteredTopWallet($userId, $from, $to, $type, $seriesFrom, $seriesTo);

        $end7 = date('Y-m-d');
        $start7 = date('Y-m-d', strtotime('-7 days'));
        $prevEnd = date('Y-m-d', strtotime('-8 days'));
        $prevStart = date('Y-m-d', strtotime('-14 days'));
        $exp7 = $repo->adminFilteredExpenseWindow($userId, $from, $to, $type, $start7, $end7);
        $expPrev = $repo->adminFilteredExpenseWindow($userId, $from, $to, $type, $prevStart, $prevEnd);
        $spendDeltaPct = null;
        if ($type !== 'income' && $expPrev > 0.01) {
            $spendDeltaPct = round(($exp7 - $expPrev) / $expPrev * 100, 1);
        }

        return [
            'analyticsSummary' => $summary,
            'analyticsSeriesFrom' => $seriesFrom,
            'analyticsSeriesTo' => $seriesTo,
            'analyticsDaily' => $daily,
            'analyticsCategoryBreakdown' => $catBreak,
            'analyticsWalletBreakdown' => $walletBreak,
            'analyticsTopCategory' => $topCat,
            'analyticsTopWallet' => $topWallet,
            'analyticsRecurringRatio' => $recurRatio,
            'analyticsSpendWeekDeltaPct' => $spendDeltaPct,
            'analyticsExpense7d' => $exp7,
        ];
    }
}
