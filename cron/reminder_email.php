<?php

declare(strict_types=1);

/**
 * CLI: php cron/reminder_email.php
 * Run daily (e.g. after midnight) to remind users of recurring items due tomorrow.
 */

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\MailService;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$pdo = Database::pdo();
$tomorrow = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');

$stmt = $pdo->prepare(
    'SELECT r.*, u.email, u.username FROM recurring_schedules r
     JOIN users u ON u.id = r.user_id
     WHERE r.is_paused = 0 AND r.next_occurrence = ? AND u.is_active = 1
       AND (r.end_date IS NULL OR r.end_date >= ?)'
);
$stmt->execute([$tomorrow, $tomorrow]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mail = new MailService();
$ins = $pdo->prepare('INSERT INTO notifications (user_id, type, title, body) VALUES (?,?,?,?)');

foreach ($rows as $r) {
    $uid = (int) $r['user_id'];
    $title = (string) $r['title'];
    $body = sprintf(
        'Your recurring "%s" (%s) is scheduled for %s.',
        $title,
        (string) $r['frequency'],
        $tomorrow
    );
    $ins->execute([$uid, 'info', 'Recurring due tomorrow', $body]);

    $email = (string) $r['email'];
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mail->send($email, 'KHFinaM: recurring tomorrow — ' . $title, $body);
    }
}

echo 'Reminder pass: ' . count($rows) . " schedule(s) for {$tomorrow}.\n";
