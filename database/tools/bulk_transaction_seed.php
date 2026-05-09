<?php

declare(strict_types=1);

/**
 * Optional: generate many randomized transactions for analytics demos.
 * Usage: php database/tools/bulk_transaction_seed.php
 */

$root = dirname(__DIR__, 2);
require $root . '/app/bootstrap.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$pdo = Database::pdo();
$row = $pdo->query("SELECT u.id AS uid, w.id AS wid FROM users u JOIN wallets w ON w.user_id = u.id WHERE u.username = 'demo' AND w.name = 'Maybank' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (! $row) {
    fwrite(STDERR, "Demo user/wallet not found. Import seed SQL first.\n");
    exit(1);
}
$uid = (int) $row['uid'];
$wid = (int) $row['wid'];

$expenseCats = $pdo->query("SELECT id FROM categories WHERE type = 'expense' AND (user_id IS NULL OR user_id = {$uid}) ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$incomeCats = $pdo->query("SELECT id FROM categories WHERE type = 'income' AND (user_id IS NULL OR user_id = {$uid}) ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
if (! $expenseCats || ! $incomeCats) {
    exit(1);
}

$ins = $pdo->prepare(
    'INSERT INTO transactions (user_id, wallet_id, category_id, type, title, amount, amount_base, currency_id, exchange_rate_to_base, transaction_date, created_by, is_consolidated_parent)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,0)'
);

$rng = static fn (int $a, int $b): int => random_int($a, $b);

for ($i = 0; $i < 220; $i++) {
    $days = $rng(1, 400);
    $d = (new DateTimeImmutable('today'))->modify('-' . $days . ' days')->format('Y-m-d');
    $income = $rng(0, 10) < 2;
    $catId = (int) ($income ? $incomeCats[$rng(0, count($incomeCats) - 1)] : $expenseCats[$rng(0, count($expenseCats) - 1)]);
    $amount = $income ? $rng(50, 800) + (random_int(0, 99) / 100) : $rng(5, 250) + (random_int(0, 99) / 100);
    $type = $income ? 'income' : 'expense';
    $title = $income ? 'Sample income #' . $i : 'Sample expense #' . $i;
    $ins->execute([$uid, $wid, $catId, $type, $title, $amount, $amount, 1, 1.0, $d, $uid]);
}

echo "Inserted 220 sample transactions for demo user.\n";
