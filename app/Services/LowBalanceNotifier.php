<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class LowBalanceNotifier
{
    public function __construct(
        private readonly WalletService $wallets = new WalletService(),
        private readonly MailService $mail = new MailService()
    ) {
    }

    /** Notify users with wallets under threshold (respects mute flag). */
    public function run(): int
    {
        $pdo = Database::pdo();
        $users = $pdo->query('SELECT id FROM users WHERE is_active = 1')->fetchAll(PDO::FETCH_COLUMN);
        $n = 0;
        foreach ($users as $uid) {
            $uid = (int) $uid;
            $st = $pdo->prepare('SELECT preference_mute_low_balance FROM users WHERE id = ?');
            $st->execute([$uid]);
            if ((int) $st->fetchColumn()) {
                continue;
            }
            $balances = $this->wallets->walletBalancesForUser($uid);
            foreach ($balances as $b) {
                if (! $b['below']) {
                    continue;
                }
                $title = 'Low balance: ' . $b['name'];
                $body = 'Balance (base est.): ' . number_format($b['balance_base'], 2) . ' — below your minimum.';
                $pdo->prepare(
                    'INSERT INTO notifications (user_id, type, title, body, data_json) VALUES (?,\'warning\',?,?,?)'
                )->execute([
                    $uid,
                    $title,
                    $body,
                    json_encode(['wallet_id' => $b['wallet_id']], JSON_THROW_ON_ERROR),
                ]);
                $n++;
                $email = $this->userEmail($pdo, $uid);
                if ($email) {
                    try {
                        $this->mail->send($email, $title, $body);
                    } catch (\Throwable) {
                    }
                }
            }
        }

        return $n;
    }

    private function userEmail(PDO $pdo, int $uid): ?string
    {
        $s = $pdo->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
        $s->execute([$uid]);

        return $s->fetchColumn() ?: null;
    }
}
