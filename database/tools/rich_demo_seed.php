<?php

declare(strict_types=1);

/**
 * Presentation-quality demo dataset for KHFinaM.
 *
 * Prerequisites: base schema + `database/seeders/002_demo_seed.sql` (currencies, superadmin, core categories).
 * Safe to re-run: removes prior rich-demo users (and rebuilds `demo`), clears superadmin finance rows, then reinserts.
 *
 * Usage (from project root):
 *   php database/tools/rich_demo_seed.php
 */

$root = dirname(__DIR__, 2);
require $root . '/app/bootstrap.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

/** @var array<int, string> */
const RICH_DEMO_USERNAMES = [
    'demo',
    'demo_office',
    'demo_freelance',
    'demo_student',
    'demo_family',
    'demo_sidebiz',
    'demo_struggle',
];

final class SeedRng
{
    private int $state;

    public function __construct(int $seed)
    {
        $this->state = $seed !== 0 ? $seed : 1;
    }

    public function int(int $min, int $max): int
    {
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }
        $this->state = ($this->state * 1103515245 + 12345) & 0x7fffffff;

        return $min + ($this->state % ($max - $min + 1));
    }

    public function float(float $min, float $max): float
    {
        $t = $this->int(0, 1_000_000) / 1_000_000;

        return round($min + $t * ($max - $min), 2);
    }

    /** @param array<int, mixed> $items */
    public function pick(array $items): mixed
    {
        if ($items === []) {
            throw new \InvalidArgumentException('empty');
        }

        return $items[$this->int(0, count($items) - 1)];
    }
}

final class RichDemoSeed
{
    private \PDO $pdo;

    private \DateTimeImmutable $today;

    /** @var array<string, int> */
    private array $walletTypeBySlug = [];

    /** @var array<string, int> */
    private array $categoryBySlug = [];

    private \PDOStatement $insTx;

    private \PDOStatement $insTransfer;

    private \PDOStatement $insRecurring;

    private \PDOStatement $insNotification;

    private \PDOStatement $insAudit;

    private \PDOStatement $insRate;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->today = new \DateTimeImmutable('today');
        $this->insTx = $pdo->prepare(
            'INSERT INTO transactions (user_id, wallet_id, from_wallet_id, to_wallet_id, category_id, parent_transaction_id, type, title, amount, amount_base, currency_id, exchange_rate_to_base, notes, transaction_date, created_by, recurring_schedule_id, is_consolidated_parent, is_internal_transfer, transfer_group)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $this->insTransfer = $pdo->prepare(
            'INSERT INTO transactions (user_id, wallet_id, from_wallet_id, to_wallet_id, category_id, parent_transaction_id, type, title, amount, amount_base, currency_id, exchange_rate_to_base, notes, transaction_date, created_by, recurring_schedule_id, is_consolidated_parent, is_internal_transfer, transfer_group)
             VALUES (?,NULL,?,?,NULL,NULL,\'transfer\',?,?,?,?,?,?,?,?,NULL,0,0,NULL)'
        );
        $this->insRecurring = $pdo->prepare(
            'INSERT INTO recurring_schedules (user_id, wallet_id, category_id, type, title, amount, currency_id, frequency, interval_value, by_weekday, by_monthday, start_date, end_date, next_occurrence, is_paused, skip_next, notes, last_generated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $this->insNotification = $pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, body, read_at, data_json) VALUES (?,?,?,?,?,?)'
        );
        $this->insAudit = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, metadata, created_at) VALUES (?,?,?,?,?,?,?,?)'
        );
        $this->insRate = $pdo->prepare(
            'INSERT INTO exchange_rates (from_currency_id, to_currency_id, rate, effective_date) VALUES (?,?,?,?)'
        );
    }

    public function run(): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->ensureWalletTypes();
            $this->ensureCategories();
            $this->loadWalletTypes();
            $this->loadCategories();
            $this->wipeRichDemo();
            $this->wipeSuperadminFinancials();
            $this->seedExchangeHistory();
            $users = $this->insertAllUsers();
            $this->seedSuperadminMinimal((int) $users['superadmin']);
            foreach (RICH_DEMO_USERNAMES as $uname) {
                $this->seedPersona($uname, (int) $users[$uname]);
            }
            $this->bulkAuditTail();
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        echo "Rich demo seed complete.\n";
        echo 'Users: ' . implode(', ', RICH_DEMO_USERNAMES) . " (password Demo@123)\n";
        echo "See database/tools/DEMO_SEED.md for persona notes.\n";
    }

    private function ensureWalletTypes(): void
    {
        $st = $this->pdo->prepare(
            'INSERT IGNORE INTO wallet_types (slug, label, icon, sort_order, is_system) VALUES (?,?,?,?,1)'
        );
        $st->execute(['savings', 'Savings', 'piggy-bank', 46]);
        $st->execute(['investment', 'Investment', 'trending_up', 55]);
    }

    private function ensureCategories(): void
    {
        $rows = [
            ['Groceries', 'groceries', 'expense', '#16a34a', 'shopping_cart', 12],
            ['Fuel', 'fuel', 'expense', '#b45309', 'local_gas_station', 13],
            ['Toll & parking', 'toll-parking', 'expense', '#ca8a04', 'toll', 14],
            ['Subscriptions', 'subscriptions', 'expense', '#7c3aed', 'subscriptions', 15],
            ['Dining out', 'dining', 'expense', '#e11d48', 'restaurant', 16],
            ['Coffee & snacks', 'coffee', 'expense', '#78350f', 'coffee', 17],
            ['Entertainment', 'entertainment', 'expense', '#db2777', 'theater_comedy', 18],
            ['Healthcare', 'healthcare', 'expense', '#0f766e', 'medical_services', 19],
            ['Rent / mortgage', 'housing', 'expense', '#4338ca', 'home', 20],
            ['Insurance', 'insurance', 'expense', '#0369a1', 'shield', 21],
            ['Loan repayment', 'loans', 'expense', '#991b1b', 'account_balance', 22],
            ['Phone & mobile', 'telecom', 'expense', '#4f46e5', 'smartphone', 23],
            ['Internet', 'internet', 'expense', '#6366f1', 'wifi', 24],
            ['Travel', 'travel', 'expense', '#0ea5e9', 'flight', 25],
            ['Fitness', 'fitness', 'expense', '#059669', 'fitness_center', 26],
            ['Education', 'education', 'expense', '#2563eb', 'school', 27],
            ['Childcare', 'childcare', 'expense', '#c026d3', 'child_care', 28],
            ['Cashback & rewards', 'cashback', 'income', '#14b8a6', 'redeem', 40],
            ['Bonus & allowance', 'bonus', 'income', '#22c55e', 'workspace_premium', 41],
            ['Refunds', 'refunds', 'income', '#84cc16', 'undo', 42],
            ['Investment returns', 'investment-income', 'income', '#a16207', 'show_chart', 43],
        ];
        $ins = $this->pdo->prepare(
            'INSERT INTO categories (user_id, parent_id, name, slug, type, color, icon, is_system, sort_order)
             SELECT NULL, NULL, ?, ?, ?, ?, ?, 1, ? FROM DUAL
             WHERE NOT EXISTS (SELECT 1 FROM categories c WHERE c.user_id IS NULL AND c.slug = ? LIMIT 1)'
        );
        foreach ($rows as $r) {
            $ins->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[1]]);
        }
    }

    private function loadWalletTypes(): void
    {
        $q = $this->pdo->query('SELECT id, slug FROM wallet_types');
        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            $this->walletTypeBySlug[(string) $row['slug']] = (int) $row['id'];
        }
    }

    private function loadCategories(): void
    {
        $q = $this->pdo->query('SELECT id, slug FROM categories WHERE user_id IS NULL AND slug IS NOT NULL');
        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            $this->categoryBySlug[(string) $row['slug']] = (int) $row['id'];
        }
    }

    private function cat(string $slug): int
    {
        if (! isset($this->categoryBySlug[$slug])) {
            throw new \InvalidArgumentException('Unknown category slug: ' . $slug);
        }

        return $this->categoryBySlug[$slug];
    }

    private function wt(string $slug): int
    {
        if (! isset($this->walletTypeBySlug[$slug])) {
            throw new \InvalidArgumentException('Unknown wallet type slug: ' . $slug);
        }

        return $this->walletTypeBySlug[$slug];
    }

    private function wipeRichDemo(): void
    {
        $superId = (int) $this->pdo->query("SELECT id FROM users WHERE username = 'superadmin' LIMIT 1")->fetchColumn();
        if ($superId <= 0) {
            throw new \RuntimeException('superadmin missing — import 002_demo_seed.sql first.');
        }
        $placeholders = implode(',', array_fill(0, count(RICH_DEMO_USERNAMES), '?'));
        $st = $this->pdo->prepare("SELECT id FROM users WHERE username IN ($placeholders)");
        $st->execute(RICH_DEMO_USERNAMES);
        /** @var array<int, string|false> $ids */
        $ids = $st->fetchAll(\PDO::FETCH_COLUMN);
        $ids = array_values(array_filter(array_map(static fn ($v) => (int) $v, $ids), static fn ($v) => $v > 0));
        if ($ids === []) {
            return;
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $this->pdo->prepare("UPDATE transactions SET created_by = ? WHERE created_by IN ($ph)")
            ->execute(array_merge([$superId], $ids));
        $this->pdo->prepare("DELETE FROM users WHERE id IN ($ph)")->execute($ids);
    }

    private function wipeSuperadminFinancials(): void
    {
        $uid = (int) $this->pdo->query("SELECT id FROM users WHERE username = 'superadmin' LIMIT 1")->fetchColumn();
        if ($uid <= 0) {
            return;
        }
        $this->pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$uid]);
        $this->pdo->prepare('DELETE FROM recurring_schedules WHERE user_id = ?')->execute([$uid]);
        $this->pdo->prepare('DELETE FROM transactions WHERE user_id = ?')->execute([$uid]);
        $this->pdo->prepare('DELETE FROM wallets WHERE user_id = ?')->execute([$uid]);
    }

    private function seedExchangeHistory(): void
    {
        $baseId = (int) $this->pdo->query('SELECT id FROM currencies WHERE is_base = 1 LIMIT 1')->fetchColumn();
        $usdId = (int) $this->pdo->query("SELECT id FROM currencies WHERE code = 'USD' LIMIT 1")->fetchColumn();
        if ($baseId <= 0 || $usdId <= 0) {
            return;
        }
        for ($m = 11; $m >= 0; $m--) {
            $d = $this->today->modify('-' . $m . ' months')->format('Y-m-01');
            $drift = 0.205 + ($m % 5) * 0.0025 - ($m * 0.0008);
            $this->insRate->execute([$baseId, $usdId, round($drift, 12), $d]);
            $this->insRate->execute([$usdId, $baseId, round(1 / $drift, 12), $d]);
        }
    }

    /** @return array<string, int> username => id */
    private function insertAllUsers(): array
    {
        $hash = password_hash('Demo@123', PASSWORD_BCRYPT);
        $ins = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role, is_active, preference_theme, preference_mute_low_balance, created_at, include_in_analytics)
             VALUES (?,?,?,?,?,?,?,?,?,0)'
        );

        $map = [];
        $superId = (int) $this->pdo->query("SELECT id FROM users WHERE username = 'superadmin' LIMIT 1")->fetchColumn();
        if ($superId <= 0) {
            throw new \RuntimeException('superadmin missing — import 002_demo_seed.sql first.');
        }
        $map['superadmin'] = $superId;

        $profiles = [
            ['demo', 'demo@khfinam.local', 'Nur Aisyah Rahman', 'dark', false, $this->today->modify('-296 days')->format('Y-m-d H:i:s')],
            ['demo_office', 'office.worker@khfinam.local', 'Chong Wei Lun', 'system', false, $this->today->modify('-261 days')->format('Y-m-d H:i:s')],
            ['demo_freelance', 'amir.design@khfinam.local', 'Amir Hakim', 'dark', false, $this->today->modify('-220 days')->format('Y-m-d H:i:s')],
            ['demo_student', 'priya.study@khfinam.local', 'Priya Lingam', 'light', false, $this->today->modify('-412 days')->format('Y-m-d H:i:s')],
            ['demo_family', 'kumar.family@khfinam.local', 'Kumar Patel (household)', 'dark', false, $this->today->modify('-189 days')->format('Y-m-d H:i:s')],
            ['demo_sidebiz', 'rizal.hustle@khfinam.local', 'Rizal Mokhtar', 'system', false, $this->today->modify('-155 days')->format('Y-m-d H:i:s')],
            ['demo_struggle', 'farah.budget@khfinam.local', 'Farah Yusof', 'light', false, $this->today->modify('-133 days')->format('Y-m-d H:i:s')],
        ];
        foreach ($profiles as $p) {
            $ins->execute([$p[0], $p[1], $hash, $p[2], 'user', 1, $p[3], $p[4] ? 1 : 0, $p[5]]);
            $map[$p[0]] = (int) $this->pdo->lastInsertId();
        }

        return $map;
    }

    private function seedSuperadminMinimal(int $superId): void
    {
        $wid = $this->createWallet($superId, 'Operations float', 'cash', 1, 0, null, 1, 0);
        $this->insertIncome($superId, $wid, $this->cat('side-income'), null, 'Petty cash top-up (ops)', 500, $this->today->modify('-140 days')->format('Y-m-d'), $superId);
        $this->insertExpense($superId, $wid, $this->cat('utilities'), null, 'Office utilities booking', 89.9, $this->today->modify('-40 days')->format('Y-m-d'), $superId);
        $this->insNotification->execute([
            $superId,
            'info',
            'Admin console',
            'Platform health OK — review audit for latest changes.',
            $this->today->modify('-2 days')->format('Y-m-d H:i:s'),
            null,
        ]);
        $this->insAudit->execute([$superId, 'login', 'session', null, '203.0.113.10', 'Mozilla/5.0 (Windows NT 10.0) Chrome/124', json_encode(['result' => 'ok'], JSON_THROW_ON_ERROR), $this->today->modify('-1 day')->format('Y-m-d 09:12:00')]);
    }

    /**
     * @param array{name:string,type_slug:string,currency_id:int,target_end:float,min:?float,is_default:bool,sort:int} $defs
     * @return array<string, int> wallet name => id
     */
    private function createWallets(int $userId, array $defs): array
    {
        $st = $this->pdo->prepare(
            'INSERT INTO wallets (user_id, name, wallet_type_id, currency_id, opening_balance, min_balance_threshold, is_default, is_active, notes, sort_order)
             VALUES (?,?,?,?,0,?,?,1,?,?)'
        );
        $map = [];
        foreach ($defs as $d) {
            $st->execute([
                $userId,
                $d['name'],
                $this->wt($d['type_slug']),
                $d['currency_id'],
                $d['min'] ?? null,
                $d['is_default'] ? 1 : 0,
                $d['notes'] ?? null,
                $d['sort'],
            ]);
            $map[$d['name']] = (int) $this->pdo->lastInsertId();
        }

        return $map;
    }

    private function createWallet(int $userId, string $name, string $typeSlug, int $currencyId, float $opening, ?float $min, int $isDefault, int $sort): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO wallets (user_id, name, wallet_type_id, currency_id, opening_balance, min_balance_threshold, is_default, is_active, notes, sort_order)
             VALUES (?,?,?,?,?,?,?,?,NULL,?)'
        );
        $st->execute([
            $userId,
            $name,
            $this->wt($typeSlug),
            $currencyId,
            $opening,
            $min,
            $isDefault,
            1,
            $sort,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertIncome(int $userId, int $walletId, int $catId, ?int $parentId, string $title, float $amount, string $date, int $createdBy, ?int $recId = null, int $isParent = 0): void
    {
        $parentId = ($parentId !== null && $parentId > 0) ? $parentId : null;
        $recId = ($recId !== null && $recId > 0) ? $recId : null;
        $this->insTx->execute([
            $userId,
            $walletId,
            null,
            null,
            $catId,
            $parentId,
            'income',
            $title,
            $amount,
            $amount,
            1,
            1.0,
            null,
            $date,
            $createdBy,
            $recId,
            $isParent,
            0,
            null,
        ]);
    }

    private function insertExpense(int $userId, int $walletId, int $catId, ?int $parentId, string $title, float $amount, string $date, int $createdBy, ?int $recId = null, int $isParent = 0): void
    {
        $parentId = ($parentId !== null && $parentId > 0) ? $parentId : null;
        $recId = ($recId !== null && $recId > 0) ? $recId : null;
        $this->insTx->execute([
            $userId,
            $walletId,
            null,
            null,
            $catId,
            $parentId,
            'expense',
            $title,
            $amount,
            $amount,
            1,
            1.0,
            null,
            $date,
            $createdBy,
            $recId,
            $isParent,
            0,
            null,
        ]);
    }

    private function insertTransfer(int $userId, int $fromId, int $toId, string $title, float $amount, string $date, int $createdBy): void
    {
        $this->insTransfer->execute([
            $userId,
            $fromId,
            $toId,
            $title,
            $amount,
            $amount,
            1,
            1.0,
            null,
            $date,
            $createdBy,
        ]);
    }

    private function reconcileWalletTargets(array $walletIdToTarget): void
    {
        $flowStmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(
                CASE
                     WHEN type = 'income' AND wallet_id = ? THEN amount_base
                     WHEN type = 'expense' AND wallet_id = ? THEN -amount_base
                     WHEN type = 'transfer' AND from_wallet_id = ? THEN -amount_base
                     WHEN type = 'transfer' AND to_wallet_id = ? THEN amount_base
                     ELSE 0 END
            ), 0) FROM transactions WHERE user_id = (SELECT user_id FROM wallets WHERE id = ? LIMIT 1) AND deleted_at IS NULL AND parent_transaction_id IS NULL"
        );
        $upd = $this->pdo->prepare('UPDATE wallets SET opening_balance = ? WHERE id = ?');
        foreach ($walletIdToTarget as $wid => $target) {
            $flowStmt->execute([$wid, $wid, $wid, $wid, $wid]);
            $flow = (float) $flowStmt->fetchColumn();
            $opening = round($target - $flow, 4);
            $upd->execute([$opening, $wid]);
        }
    }

    private function seedPersona(string $username, int $userId): void
    {
        $rng = new SeedRng(crc32($username) ^ 0x5f3759df);
        $start = $this->today->modify('-11 months');
        $end = $this->today;

        match ($username) {
            'demo' => $this->seedShowcaseDemo($userId, $rng, $start, $end),
            'demo_office' => $this->seedOffice($userId, $rng, $start, $end),
            'demo_freelance' => $this->seedFreelance($userId, $rng, $start, $end),
            'demo_student' => $this->seedStudent($userId, $rng, $start, $end),
            'demo_family' => $this->seedFamily($userId, $rng, $start, $end),
            'demo_sidebiz' => $this->seedSidebiz($userId, $rng, $start, $end),
            'demo_struggle' => $this->seedStruggle($userId, $rng, $start, $end),
            default => null,
        };
    }

    private function seedShowcaseDemo(int $userId, SeedRng $rng, \DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        $w = $this->createWallets($userId, [
            ['name' => 'Maybank Current', 'type_slug' => 'bank', 'currency_id' => 1, 'target_end' => 0, 'min' => 400, 'is_default' => true, 'sort' => 0, 'notes' => 'Salary + bills'],
            ['name' => 'Emergency savings', 'type_slug' => 'savings', 'currency_id' => 1, 'target_end' => 0, 'min' => null, 'is_default' => false, 'sort' => 1, 'notes' => '6-month buffer target'],
            ['name' => 'Touch n Go', 'type_slug' => 'ewallet', 'currency_id' => 1, 'target_end' => 0, 'min' => 80, 'is_default' => false, 'sort' => 2, 'notes' => 'Tolls + quick pay'],
            ['name' => 'GrabPay', 'type_slug' => 'ewallet', 'currency_id' => 1, 'target_end' => 0, 'min' => 40, 'is_default' => false, 'sort' => 3, 'notes' => 'Food delivery'],
            ['name' => 'Cash wallet', 'type_slug' => 'cash', 'currency_id' => 1, 'target_end' => 0, 'min' => 60, 'is_default' => false, 'sort' => 4, 'notes' => 'Petty cash'],
            ['name' => 'Visa Platinum', 'type_slug' => 'credit_card', 'currency_id' => 1, 'target_end' => 0, 'min' => null, 'is_default' => false, 'sort' => 5, 'notes' => 'Revolving — clear monthly'],
        ]);
        $bank = $w['Maybank Current'];
        $sav = $w['Emergency savings'];
        $tng = $w['Touch n Go'];
        $grab = $w['GrabPay'];
        $cash = $w['Cash wallet'];
        $card = $w['Visa Platinum'];

        $ridSalary = $this->insertRecurringRow($userId, $bank, $this->cat('salary'), 'income', 'ACME Malaysia — salary', 8200, 'monthly', 1, $this->today->modify('-18 months')->format('Y-m-d'), null, $this->today->modify('+6 days')->format('Y-m-d'), false, false, 'Credited end of month', $this->today->modify('-32 days')->format('Y-m-d H:i:s'));
        $ridNetflix = $this->insertRecurringRow($userId, $card, $this->cat('subscriptions'), 'expense', 'Netflix', 54.9, 'monthly', 1, $this->today->modify('-14 months')->format('Y-m-d'), null, $this->today->modify('+3 days')->format('Y-m-d'), false, false, null, $this->today->modify('-61 days')->format('Y-m-d H:i:s'));
        $ridSpotify = $this->insertRecurringRow($userId, $card, $this->cat('subscriptions'), 'expense', 'Spotify Premium', 15.9, 'monthly', 1, $this->today->modify('-11 months')->format('Y-m-d'), null, $this->today->modify('+11 days')->format('Y-m-d'), true, false, 'Paused during travel month', null);
        $ridGym = $this->insertRecurringRow($userId, $bank, $this->cat('fitness'), 'expense', 'Fitness First', 189, 'monthly', 1, $this->today->modify('-10 months')->format('Y-m-d'), null, $this->today->modify('+18 days')->format('Y-m-d'), false, false, null, $this->today->modify('-28 days')->format('Y-m-d H:i:s'));
        $ridPhone = $this->insertRecurringRow($userId, $bank, $this->cat('telecom'), 'expense', 'Postpaid — Maxis', 98, 'monthly', 1, $this->today->modify('-20 months')->format('Y-m-d'), null, $this->today->modify('+5 days')->format('Y-m-d'), false, false, 'Due 2nd — next debit scheduled ahead', $this->today->modify('-400 days')->format('Y-m-d H:i:s'));

        for ($i = 0; $i < 11; $i++) {
            $monthStart = $start->modify('+' . $i . ' months')->modify('first day of this month');
            if ($monthStart > $end) {
                break;
            }
            $daySal = $rng->int(24, 28);
            $dSal = $monthStart->setDate((int) $monthStart->format('Y'), (int) $monthStart->format('m'), min($daySal, (int) $monthStart->format('t')))->format('Y-m-d');
            $this->insertIncome($userId, $bank, $this->cat('salary'), null, 'ACME Malaysia — salary', 8200 + ($i % 3 === 0 ? 400 : 0), $dSal, $userId, $ridSalary);

            if ($i % 3 === 1) {
                $dFr = $monthStart->modify('+' . $rng->int(4, 18) . ' days')->format('Y-m-d');
                $this->insertIncome($userId, $bank, $this->cat('freelance'), null, 'Invoiced UX retainer — Client Nova', (float) $rng->int(1200, 2800), $dFr, $userId);
            }

            $this->insertExpense($userId, $bank, $this->cat('internet'), null, 'Time fibre 500Mbps', 139, $monthStart->modify('+' . $rng->int(2, 5) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $bank, $this->cat('utilities'), null, 'TNB electricity', (float) $rng->int(95, 220), $monthStart->modify('+' . $rng->int(6, 12) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $bank, $this->cat('insurance'), null, 'Medical card — prorated', (float) $rng->int(210, 265), $monthStart->modify('+' . $rng->int(1, 7) . ' days')->format('Y-m-d'), $userId);

            $weeks = $rng->int(3, 5);
            for ($k = 0; $k < $weeks; $k++) {
                $d = $monthStart->modify('+' . $rng->int(0, 27) . ' days');
                if ($d > $end) {
                    break;
                }
                $this->insertExpense($userId, $rng->pick([$bank, $tng, $cash]), $this->cat('groceries'), null, 'Groceries — ' . $rng->pick(['Jaya Grocer', 'Village Grocer', 'Lotus', 'AEON']), (float) $rng->int(55, 198), $d->format('Y-m-d'), $userId);
            }

            for ($k = 0; $k < $rng->int(4, 9); $k++) {
                $d = $monthStart->modify('+' . $rng->int(0, 28) . ' days');
                $merchants = [
                    [$grab, $this->cat('dining'), 'GrabFood — dinner', 18, 55],
                    [$cash, $this->cat('coffee'), 'Kopitiam breakfast', 6, 18],
                    [$card, $this->cat('shopping'), 'Online — Shopee household', 35, 220],
                    [$tng, $this->cat('toll-parking'), 'Highway / parking', 8, 35],
                    [$bank, $this->cat('fuel'), 'Petronas — fuel', 70, 160],
                    [$card, $this->cat('entertainment'), 'Movie / weekend', 35, 120],
                ];
                $pick = $merchants[$rng->int(0, count($merchants) - 1)];
                $this->insertExpense($userId, $pick[0], $pick[1], null, $pick[2], (float) $rng->int($pick[3], $pick[4]), $d->format('Y-m-d'), $userId);
            }

            $this->insertTransfer($userId, $bank, $tng, 'Top up eWallet for tolls', (float) $rng->int(120, 260), $monthStart->modify('+' . $rng->int(1, 8) . ' days')->format('Y-m-d'), $userId);
            $this->insertTransfer($userId, $bank, $sav, 'Salary day emergency fund', (float) $rng->int(400, 900), $monthStart->modify('+' . $rng->int(26, 30) . ' days')->format('Y-m-d'), $userId);
            $this->insertTransfer($userId, $tng, $cash, 'Cash out for pasar malam', (float) $rng->int(40, 90), $monthStart->modify('+' . $rng->int(10, 22) . ' days')->format('Y-m-d'), $userId);

            if ($i % 4 === 0) {
                $this->insertIncome($userId, $bank, $this->cat('cashback'), null, 'Card cashback promo', (float) $rng->int(12, 88), $monthStart->modify('+' . 20 . ' days')->format('Y-m-d'), $userId);
            }
        }

        $this->seedConsolidatedGroceryTrip($userId, $bank, $userId, $rng, $this->today->modify('-43 days')->format('Y-m-d'), 387.42, [
            ['Fresh produce — salad & fruit', $this->cat('groceries'), 84.9],
            ['Bakery & dairy', $this->cat('groceries'), 56.5],
            ['Household refill', $this->cat('utilities'), 45.85],
        ]);
        $this->seedConsolidatedGroceryTrip($userId, $bank, $userId, $rng, $this->today->modify('-118 days')->format('Y-m-d'), 428.2, [
            ['BBQ ingredients', $this->cat('groceries'), 168],
            ['Sauces & drinks', $this->cat('groceries'), 79.35],
            ['Disposable utensils', $this->cat('shopping'), 61.08],
        ]);
        $tripParent = ['Penang weekend — envelopes', $this->cat('travel'), 1240.55, $this->today->modify('-76 days')->format('Y-m-d')];
        $this->insTx->execute([$userId, $bank, null, null, $tripParent[1], null, 'expense', $tripParent[0], $tripParent[2], $tripParent[2], 1, 1.0, 'Airbnb + fuel + meals', $tripParent[3], $userId, null, 1, 0, null]);
        $pid = (int) $this->pdo->lastInsertId();
        $this->insertExpense($userId, $bank, $this->cat('travel'), $pid, 'Homestay — 2 nights', 520, $tripParent[3], $userId, null, 0);
        $this->insertExpense($userId, $bank, $this->cat('fuel'), $pid, 'Fuel there & back', 185, $tripParent[3], $userId, null, 0);
        $this->insertExpense($userId, $bank, $this->cat('dining'), $pid, 'Coastal seafood dinner', 220, $tripParent[3], $userId, null, 0);
        $this->insertExpense($userId, $bank, $this->cat('entertainment'), $pid, 'Museum + parking', 95.5, $tripParent[3], $userId, null, 0);
        $this->insertExpense($userId, $bank, $this->cat('coffee'), $pid, 'Road snacks & kuih stash', 220.05, $tripParent[3], $userId, null, 0);

        $this->insNotification->execute([$userId, 'info', 'Recurring due soon', 'Fitness First posts in about a week.', null, null]);
        $this->insNotification->execute([
            $userId,
            'warning',
            'TNG balance watch',
            'Top up before the long weekend toll queue.',
            null,
            json_encode(['wallet' => 'Touch n Go'], JSON_THROW_ON_ERROR),
        ]);
        $this->insNotification->execute([
            $userId,
            'info',
            'Bonus season',
            'Company profit share landed—nice bump to savings rate.',
            $this->today->modify('-40 days')->format('Y-m-d H:i:s'),
            null,
        ]);

        $targets = [$bank => 5200.0, $sav => 14800.0, $tng => 220.0, $grab => 95.0, $cash => 185.0, $card => -890.0];
        $this->reconcileWalletTargets($targets);
    }

    /**
     * @param array<int, array{0:string,1:int,2:float}> $lines
     */
    private function seedConsolidatedGroceryTrip(int $userId, int $walletId, int $createdBy, SeedRng $rng, string $date, float $parentTotal, array $lines): void
    {
        $sum = array_sum(array_column($lines, 2));
        if (abs($sum - $parentTotal) > 0.02) {
            $lines[count($lines) - 1][2] += $parentTotal - $sum;
        }
        $this->insTx->execute([$userId, $walletId, null, null, $this->cat('groceries'), null, 'expense', 'Groceries — consolidated basket', $parentTotal, $parentTotal, 1, 1.0, 'Split across aisles', $date, $createdBy, null, 1, 0, null]);
        $pid = (int) $this->pdo->lastInsertId();
        foreach ($lines as $L) {
            $this->insertExpense($userId, $walletId, $L[1], $pid, $L[0], $L[2], $date, $createdBy, null, 0);
        }
    }

    private function insertRecurringRow(
        int $userId,
        int $walletId,
        int $catId,
        string $type,
        string $title,
        float $amount,
        string $frequency,
        int $interval,
        string $start,
        ?string $end,
        string $next,
        bool $paused,
        bool $skipNext,
        ?string $notes,
        ?string $lastGen
    ): int {
        $this->insRecurring->execute([
            $userId,
            $walletId,
            $catId,
            $type,
            $title,
            $amount,
            1,
            $frequency,
            $interval,
            null,
            null,
            $start,
            $end,
            $next,
            $paused ? 1 : 0,
            $skipNext ? 1 : 0,
            $notes,
            $lastGen,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function seedOffice(int $userId, SeedRng $rng, \DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        $w = $this->createWallets($userId, [
            ['name' => 'Public Bank Salary', 'type_slug' => 'bank', 'currency_id' => 1, 'target_end' => 0, 'min' => 300, 'is_default' => true, 'sort' => 0, 'notes' => null],
            ['name' => 'Touch n Go', 'type_slug' => 'ewallet', 'currency_id' => 1, 'target_end' => 0, 'min' => 50, 'is_default' => false, 'sort' => 1, 'notes' => null],
            ['name' => 'Cash', 'type_slug' => 'cash', 'currency_id' => 1, 'target_end' => 0, 'min' => 30, 'is_default' => false, 'sort' => 2, 'notes' => null],
        ]);
        $b = $w['Public Bank Salary'];
        $t = $w['Touch n Go'];
        $c = $w['Cash'];
        $this->insertRecurringRow($userId, $b, $this->cat('salary'), 'income', 'Employer payroll', 6100, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+4 days')->format('Y-m-d'), false, false, null, $this->today->modify('-30 days')->format('Y-m-d H:i:s'));
        $this->insertRecurringRow($userId, $b, $this->cat('housing'), 'expense', 'Room rental — SS15', 950, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+2 days')->format('Y-m-d'), false, false, null, null);
        $this->insertRecurringRow($userId, $b, $this->cat('subscriptions'), 'expense', 'Netflix + YouTube Premium bundle', 68, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+16 days')->format('Y-m-d'), false, false, null, $this->today->modify('-58 days')->format('Y-m-d H:i:s'));

        for ($i = 0; $i < 11; $i++) {
            $m = $start->modify('+' . $i . ' months');
            if ($m > $end) {
                break;
            }
            $this->insertIncome($userId, $b, $this->cat('salary'), null, 'Employer payroll', 6100, $m->modify('+' . $rng->int(25, 28) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $b, $this->cat('housing'), null, 'Room rental — SS15', 950, $m->modify('+' . $rng->int(4, 6) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $b, $this->cat('telecom'), null, 'U Mobile postpaid', 78, $m->modify('+3 days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $t, $this->cat('transport'), null, 'RapidKL + MRT top ups', (float) $rng->int(110, 185), $m->modify('+' . $rng->int(5, 20) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $c, $this->cat('dining'), null, 'Office lunch — mixed', (float) $rng->int(220, 420), $m->modify('+' . $rng->int(0, 25) . ' days')->format('Y-m-d'), $userId);
            $this->insertTransfer($userId, $b, $t, 'Load TNG for commute', (float) $rng->int(150, 240), $m->modify('+1 days')->format('Y-m-d'), $userId);
        }
        $this->insNotification->execute([
            $userId,
            'info',
            'Rental reminder',
            'Landlord expects transfer by the 5th.',
            $this->today->modify('-9 days')->format('Y-m-d H:i:s'),
            null,
        ]);
        $this->reconcileWalletTargets([$b => 2100.0, $t => 160.0, $c => 85.0]);
    }

    private function seedFreelance(int $userId, SeedRng $rng, \DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        $w = $this->createWallets($userId, [
            ['name' => 'Maybiz Current', 'type_slug' => 'bank', 'currency_id' => 1, 'target_end' => 0, 'min' => 150, 'is_default' => true, 'sort' => 0, 'notes' => 'GST aware'],
            ['name' => 'Tax savings pot', 'type_slug' => 'savings', 'currency_id' => 1, 'target_end' => 0, 'min' => null, 'is_default' => false, 'sort' => 1, 'notes' => 'Quarterly provisioning'],
            ['name' => 'GrabPay freelancer', 'type_slug' => 'ewallet', 'currency_id' => 1, 'target_end' => 0, 'min' => null, 'is_default' => false, 'sort' => 2, 'notes' => null],
        ]);
        $b = $w['Maybiz Current'];
        $tax = $w['Tax savings pot'];
        $g = $w['GrabPay freelancer'];
        $this->insertRecurringRow($userId, $b, $this->cat('internet'), 'expense', 'SME fibre', 169, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+7 days')->format('Y-m-d'), false, false, null, null);
        $this->insertRecurringRow($userId, $b, $this->cat('subscriptions'), 'expense', 'Adobe Creative Cloud', 228, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+14 days')->format('Y-m-d'), false, false, null, $this->today->modify('-45 days')->format('Y-m-d H:i:s'));

        for ($i = 0; $i < 11; $i++) {
            $m = $start->modify('+' . $i . ' months');
            if ($m > $end) {
                break;
            }
            $invoices = $rng->int(1, 3);
            for ($x = 0; $x < $invoices; $x++) {
                $amt = (float) $rng->int(2200, 9800);
                $this->insertIncome($userId, $b, $this->cat('freelance'), null, 'Brand kit — Milestone ' . ($x + 1), $amt, $m->modify('+' . $rng->int(1, 24) . ' days')->format('Y-m-d'), $userId);
                $this->insertTransfer($userId, $b, $tax, 'Move ~8% for tax buffer', round($amt * 0.08, 2), $m->modify('+' . $rng->int(25, 28) . ' days')->format('Y-m-d'), $userId);
            }
            if ($rng->int(0, 4) === 0) {
                $this->insertIncome($userId, $b, $this->cat('refunds'), null, 'Client scope refund (unused hours)', (float) $rng->int(120, 450), $m->modify('+' . 10 . ' days')->format('Y-m-d'), $userId);
            }
            $this->insertExpense($userId, $b, $this->cat('shopping'), null, 'Monitor upgrade (partial claim)', (float) $rng->int(400, 1900), $m->modify('+' . $rng->int(3, 18) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $g, $this->cat('dining'), null, 'Client meet — coffee', (float) $rng->int(25, 65), $m->modify('+' . $rng->int(6, 22) . ' days')->format('Y-m-d'), $userId);
        }
        $this->insNotification->execute([
            $userId,
            'info',
            'Invoice #1044 paid',
            'Payment received — Bank transfer MYR 6,200.',
            null,
            null,
        ]);
        $this->reconcileWalletTargets([$b => 8400.0, $tax => 6200.0, $g => 45.0]);
    }

    private function seedStudent(int $userId, SeedRng $rng, \DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        $w = $this->createWallets($userId, [
            ['name' => 'Mum allowance account', 'type_slug' => 'bank', 'currency_id' => 1, 'target_end' => 0, 'min' => 50, 'is_default' => true, 'sort' => 0, 'notes' => 'Parents transfer here'],
            ['name' => 'Cash', 'type_slug' => 'cash', 'currency_id' => 1, 'target_end' => 0, 'min' => 10, 'is_default' => false, 'sort' => 1, 'notes' => null],
            ['name' => 'TNG Student', 'type_slug' => 'ewallet', 'currency_id' => 1, 'target_end' => 0, 'min' => 15, 'is_default' => false, 'sort' => 2, 'notes' => null],
        ]);
        $b = $w['Mum allowance account'];
        $c = $w['Cash'];
        $t = $w['TNG Student'];
        $this->insertRecurringRow($userId, $b, $this->cat('bonus'), 'income', 'Parents allowance deposit', 950, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+8 days')->format('Y-m-d'), false, false, null, $this->today->modify('-35 days')->format('Y-m-d H:i:s'));
        $this->insertRecurringRow($userId, $b, $this->cat('subscriptions'), 'expense', 'Spotify Student (shared family plan)', 8.9, 'monthly', 1, $start->modify('+2 months')->format('Y-m-d'), null, $this->today->modify('+20 days')->format('Y-m-d'), true, false, 'Paused — using parent account for now', null);

        for ($i = 0; $i < 11; $i++) {
            $m = $start->modify('+' . $i . ' months');
            if ($m > $end) {
                break;
            }
            $this->insertIncome($userId, $b, $this->cat('bonus'), null, 'Allowance transfer — mum', 950, $m->modify('+2 days')->format('Y-m-d'), $userId);
            if ($rng->int(0, 2) !== 0) {
                $this->insertIncome($userId, $b, $this->cat('side-income'), null, 'Campus lab assistant', (float) $rng->int(280, 520), $m->modify('+' . $rng->int(10, 24) . ' days')->format('Y-m-d'), $userId);
            }
            $this->insertExpense($userId, $b, $this->cat('education'), null, 'Software license / books', (float) $rng->int(35, 180), $m->modify('+6 days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $c, $this->cat('food'), null, 'Cafeteria + mamak', (float) $rng->int(120, 260), $m->modify('+' . $rng->int(1, 20) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $t, $this->cat('transport'), null, 'Bus / quick ride', (float) $rng->int(35, 90), $m->modify('+' . $rng->int(5, 18) . ' days')->format('Y-m-d'), $userId);
            $this->insertTransfer($userId, $b, $t, 'Load TNG for the week', (float) $rng->int(40, 75), $m->modify('+4 days')->format('Y-m-d'), $userId);
        }
        $this->reconcileWalletTargets([$b => 640.0, $c => 55.0, $t => 48.0]);
    }

    private function seedFamily(int $userId, SeedRng $rng, \DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        $w = $this->createWallets($userId, [
            ['name' => 'Joint Household — HSBC', 'type_slug' => 'bank', 'currency_id' => 1, 'target_end' => 0, 'min' => 1200, 'is_default' => true, 'sort' => 0, 'notes' => 'Primary bills'],
            ['name' => 'Kids & school fund', 'type_slug' => 'savings', 'currency_id' => 1, 'target_end' => 0, 'min' => null, 'is_default' => false, 'sort' => 1, 'notes' => null],
            ['name' => 'GrabPay Household', 'type_slug' => 'ewallet', 'currency_id' => 1, 'target_end' => 0, 'min' => 100, 'is_default' => false, 'sort' => 2, 'notes' => null],
            ['name' => 'Cash petty', 'type_slug' => 'cash', 'currency_id' => 1, 'target_end' => 0, 'min' => 80, 'is_default' => false, 'sort' => 3, 'notes' => null],
        ]);
        $b = $w['Joint Household — HSBC'];
        $kids = $w['Kids & school fund'];
        $grab = $w['GrabPay Household'];
        $cash = $w['Cash petty'];
        $this->insertRecurringRow($userId, $b, $this->cat('salary'), 'income', 'Merged salaries — Household', 11200, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+5 days')->format('Y-m-d'), false, false, null, $this->today->modify('-27 days')->format('Y-m-d H:i:s'));
        $this->insertRecurringRow($userId, $b, $this->cat('housing'), 'expense', 'Condominium maintenance + mortgage chunk', 3200, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+1 days')->format('Y-m-d'), false, false, null, null);
        $this->insertRecurringRow($userId, $b, $this->cat('insurance'), 'expense', 'Family medical + life bundle', 640, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+12 days')->format('Y-m-d'), false, false, null, $this->today->modify('-95 days')->format('Y-m-d H:i:s'));

        for ($i = 0; $i < 11; $i++) {
            $m = $start->modify('+' . $i . ' months');
            if ($m > $end) {
                break;
            }
            $this->insertIncome($userId, $b, $this->cat('salary'), null, 'Household salaries pool', 11200 + ($i % 4 === 0 ? 800 : 0), $m->modify('+' . $rng->int(24, 28) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $b, $this->cat('housing'), null, 'Condo maintenance + mortgage tranche', 3200, $m->modify('+' . $rng->int(1, 5) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $b, $this->cat('childcare'), null, 'Daycare / kindergarten block fee', (float) $rng->int(900, 1250), $m->modify('+4 days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $cash, $this->cat('groceries'), null, 'Wet market + bulk dry goods', (float) $rng->int(280, 520), $m->modify('+' . $rng->int(2, 12) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $grab, $this->cat('dining'), null, 'Family weekend brunch', (float) $rng->int(85, 210), $m->modify('+' . $rng->int(6, 20) . ' days')->format('Y-m-d'), $userId);
            $this->insertTransfer($userId, $b, $kids, 'Education sinking fund', (float) $rng->int(700, 1200), $m->modify('+' . $rng->int(26, 29) . ' days')->format('Y-m-d'), $userId);
            $this->insertTransfer($userId, $b, $grab, 'Top up household eWallet', (float) $rng->int(200, 360), $m->modify('+3 days')->format('Y-m-d'), $userId);
        }
        $this->seedConsolidatedGroceryTrip($userId, $cash, $userId, $rng, $this->today->modify('-17 days')->format('Y-m-d'), 512.3, [
            ['Fresh fish & poultry', $this->cat('groceries'), 148],
            ['Vegetables', $this->cat('groceries'), 62],
            ['Diapers & wipes', $this->cat('shopping'), 96.5],
        ]);
        $this->insNotification->execute([
            $userId,
            'warning',
            'Daycare fee posted',
            'Invoice #882 ready — amount matches recurring.',
            null,
            null,
        ]);
        $this->reconcileWalletTargets([$b => 11800.0, $kids => 22400.0, $grab => 140.0, $cash => 240.0]);
    }

    private function seedSidebiz(int $userId, SeedRng $rng, \DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        $w = $this->createWallets($userId, [
            ['name' => 'Day job — Maybank', 'type_slug' => 'bank', 'currency_id' => 1, 'target_end' => 0, 'min' => 500, 'is_default' => true, 'sort' => 0, 'notes' => null],
            ['name' => 'Shopee biz wallet', 'type_slug' => 'ewallet', 'currency_id' => 1, 'target_end' => 0, 'min' => null, 'is_default' => false, 'sort' => 1, 'notes' => 'Side income settlement'],
            ['name' => 'Side income bank', 'type_slug' => 'bank', 'currency_id' => 1, 'target_end' => 0, 'min' => 200, 'is_default' => false, 'sort' => 2, 'notes' => 'Split for accounting'],
        ]);
        $day = $w['Day job — Maybank'];
        $sp = $w['Shopee biz wallet'];
        $side = $w['Side income bank'];
        $this->insertRecurringRow($userId, $day, $this->cat('salary'), 'income', 'Employer salary', 5400, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+9 days')->format('Y-m-d'), false, false, null, $this->today->modify('-31 days')->format('Y-m-d H:i:s'));
        $this->insertRecurringRow($userId, $side, $this->cat('loans'), 'expense', 'Car hire purchase', 890, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+20 days')->format('Y-m-d'), false, false, null, null);

        for ($i = 0; $i < 11; $i++) {
            $m = $start->modify('+' . $i . ' months');
            if ($m > $end) {
                break;
            }
            $this->insertIncome($userId, $day, $this->cat('salary'), null, 'Employer salary', 5400, $m->modify('+' . 27 . ' days')->format('Y-m-d'), $userId);
            $this->insertIncome($userId, $sp, $this->cat('side-income'), null, 'Shopee seller payout — batch', (float) $rng->int(450, 2400), $m->modify('+' . $rng->int(5, 19) . ' days')->format('Y-m-d'), $userId);
            $this->insertTransfer($userId, $sp, $side, 'Sweep payouts to bookkeeping account', (float) $rng->int(300, 1800), $m->modify('+' . 21 . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $day, $this->cat('groceries'), null, 'Household staples', (float) $rng->int(260, 480), $m->modify('+6 days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $side, $this->cat('shopping'), null, 'Restock SKU — packaging', (float) $rng->int(120, 380), $m->modify('+9 days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $side, $this->cat('loans'), null, 'Hire purchase', 890, $m->modify('+' . 20 . ' days')->format('Y-m-d'), $userId);
            if ($i % 5 === 0) {
                $this->insertIncome($userId, $side, $this->cat('bonus'), null, 'Company performance bonus', 1200, $m->modify('+' . 10 . ' days')->format('Y-m-d'), $userId);
            }
        }
        $this->insNotification->execute([
            $userId,
            'info',
            'Payout landed',
            'Shopee released MYR 1,088 to your biz wallet.',
            null,
            null,
        ]);
        $this->reconcileWalletTargets([$day => 2100.0, $sp => 220.0, $side => 5100.0]);
    }

    private function seedStruggle(int $userId, SeedRng $rng, \DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        $w = $this->createWallets($userId, [
            ['name' => 'Salary account — BSN', 'type_slug' => 'bank', 'currency_id' => 1, 'target_end' => 0, 'min' => 200, 'is_default' => true, 'sort' => 0, 'notes' => 'Often tight before payday'],
            ['name' => 'TNG', 'type_slug' => 'ewallet', 'currency_id' => 1, 'target_end' => 0, 'min' => 35, 'is_default' => false, 'sort' => 1, 'notes' => null],
            ['name' => 'Cash', 'type_slug' => 'cash', 'currency_id' => 1, 'target_end' => 0, 'min' => 25, 'is_default' => false, 'sort' => 2, 'notes' => null],
        ]);
        $b = $w['Salary account — BSN'];
        $t = $w['TNG'];
        $c = $w['Cash'];
        $this->insertRecurringRow($userId, $b, $this->cat('salary'), 'income', 'Junior executive pay', 3200, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+7 days')->format('Y-m-d'), false, false, null, $this->today->modify('-33 days')->format('Y-m-d H:i:s'));
        $this->insertRecurringRow($userId, $b, $this->cat('housing'), 'expense', 'Room + utilities split', 850, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+2 days')->format('Y-m-d'), false, false, null, null);
        $this->insertRecurringRow($userId, $b, $this->cat('loans'), 'expense', 'PTPTN / personal loan slab', 280, 'monthly', 1, $start->format('Y-m-d'), null, $this->today->modify('+19 days')->format('Y-m-d'), false, false, null, $this->today->modify('-62 days')->format('Y-m-d H:i:s'));

        for ($i = 0; $i < 11; $i++) {
            $m = $start->modify('+' . $i . ' months');
            if ($m > $end) {
                break;
            }
            $this->insertIncome($userId, $b, $this->cat('salary'), null, 'Salary', 3200, $m->modify('+' . $rng->int(26, 29) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $b, $this->cat('housing'), null, 'Rent + utils', 850, $m->modify('+3 days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $b, $this->cat('loans'), null, 'Loan schedule', 280, $m->modify('+7 days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $b, $this->cat('telecom'), null, 'Prepaid/data', 55, $m->modify('+8 days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $t, $this->cat('groceries'), null, 'Compact grocery runs', (float) $rng->int(45, 120), $m->modify('+' . $rng->int(4, 22) . ' days')->format('Y-m-d'), $userId);
            $this->insertExpense($userId, $c, $this->cat('transport'), null, 'Grab when running late', (float) $rng->int(18, 55), $m->modify('+' . $rng->int(1, 25) . ' days')->format('Y-m-d'), $userId);
            $this->insertTransfer($userId, $b, $t, 'Small TNG load', (float) $rng->int(40, 70), $m->modify('+5 days')->format('Y-m-d'), $userId);
        }
        $this->insNotification->execute([
            $userId,
            'warning',
            'Low balance shield',
            'BSN wallet is nearing your RM200 floor after bills.',
            null,
            json_encode(['wallet' => 'BSN'], JSON_THROW_ON_ERROR),
        ]);
        $this->insNotification->execute([
            $userId,
            'info',
            'Loan reminder',
            'Next loan debit is flagged on its recurring schedule.',
            $this->today->modify('-4 days')->format('Y-m-d H:i:s'),
            null,
        ]);
        $this->reconcileWalletTargets([$b => 180.0, $t => 22.0, $c => 18.0]);
    }

    private function bulkAuditTail(): void
    {
        $rows = [
            [null, 'user_create', 'user', '24', '198.51.100.22', 'Mozilla/5.0', '{"by":"superadmin"}', $this->today->modify('-88 days')->format('Y-m-d 15:02:01')],
            ['demo_freelance', 'login', 'session', null, '192.168.50.62', 'KHFinaM-Mobile/1.0', '{"mfa":"off"}', $this->today->modify('-41 days')->format('Y-m-d 08:44:09')],
            ['demo_family', 'transaction_create', 'transaction', '910', '203.0.113.51', 'Chrome/126', '{"src":"mobile"}', $this->today->modify('-31 days')->format('Y-m-d 19:10:51')],
            ['demo', 'transaction_update', 'transaction', '120', '10.12.14.88', 'Chrome/126', '{"field":"notes"}', $this->today->modify('-54 days')->format('Y-m-d 11:18:42')],
            ['demo_student', 'wallet_create', 'wallet', '44', '198.18.0.9', 'Safari', '{"name":"TNG Student"}', $this->today->modify('-120 days')->format('Y-m-d 13:01:00')],
            ['demo_sidebiz', 'transaction_delete', 'transaction', '502', '198.18.0.10', 'Chrome', '{"undo":false}', $this->today->modify('-69 days')->format('Y-m-d 21:33:11')],
            ['superadmin', 'settings_update', 'settings', 'global', '127.0.0.1', 'curl/8.5', '{"keys":["smtp_port"]}', $this->today->modify('-14 days')->format('Y-m-d 10:00:01')],
            ['demo_struggle', 'logout', 'session', null, '198.18.0.33', 'Firefox/125', '{}', $this->today->modify('-3 days')->format('Y-m-d 23:18:00')],
            ['demo_office', 'notification_prefs', 'user', null, '10.77.3.21', 'Mobile', '{"mute_low_balance":false}', $this->today->modify('-22 days')->format('Y-m-d 07:41:56')],
        ];

        foreach ($rows as $r) {
            $uid = null;
            if ($r[0] !== null && $r[0] !== '') {
                $s = $this->pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
                $s->execute([$r[0]]);
                $col = $s->fetchColumn();
                $uid = $col !== false ? (int) $col : null;
            }
            $this->insAudit->execute([
                $uid,
                $r[1],
                $r[2],
                $r[3],
                $r[4],
                $r[5],
                $r[6],
                $r[7],
            ]);
        }

        $this->insAudit->execute([
            null,
            'password_reset_request',
            'user',
            'demo_freelance',
            '203.0.113.88',
            'SMTP-cli',
            json_encode(['channel' => 'email'], JSON_THROW_ON_ERROR),
            $this->today->modify('-50 days')->format('Y-m-d 16:03:44'),
        ]);
    }
}

(new RichDemoSeed(Database::pdo()))->run();
