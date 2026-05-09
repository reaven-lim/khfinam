<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Config;
use PDO;

final class MailService
{
    public function send(string $to, string $subject, string $body): bool
    {
        $from = Config::get('app.mail_from_address', 'noreply@localhost');
        $fromName = Config::get('app.mail_from_name', 'KHFinaM');
        $settings = $this->globalSettings();

        if (! empty($settings['smtp_host'])) {
            return $this->sendViaSmtp($settings, $to, $subject, $body, $from, $fromName);
        }

        $headers = [
            'From: ' . $this->encodeHeader($fromName) . ' <' . $from . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return @mail($to, $this->encodeHeader($subject), $body, implode("\r\n", $headers));
    }

    /** @return array<string, string> */
    private function globalSettings(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query(
            "SELECT key_name, value FROM settings WHERE scope = 'global' AND user_id IS NULL AND key_name LIKE 'smtp_%'"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['key_name']] = (string) $r['value'];
        }

        return $out;
    }

    /** @param array<string, string> $s */
    private function sendViaSmtp(array $s, string $to, string $subject, string $body, string $from, string $fromName): bool
    {
        $host = $s['smtp_host'] ?? '';
        $port = (int) ($s['smtp_port'] ?? 587);
        $user = $s['smtp_user'] ?? '';
        $pass = $s['smtp_pass'] ?? '';
        $enc = $s['smtp_encryption'] ?? 'tls';

        if ($host === '') {
            return false;
        }

        $remote = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errStr, 15, STREAM_CLIENT_CONNECT);
        if (! $fp) {
            return false;
        }
        stream_set_timeout($fp, 15);
        $this->smtpExpect($fp, '220');
        $this->smtpWrite($fp, 'EHLO localhost' . "\r\n");
        $this->smtpReadMultiline($fp);

        if ($enc === 'tls' && $host !== '') {
            $this->smtpWrite($fp, "STARTTLS\r\n");
            $this->smtpExpect($fp, '220');
            if (! stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($fp);

                return false;
            }
            $this->smtpWrite($fp, 'EHLO localhost' . "\r\n");
            $this->smtpReadMultiline($fp);
        }

        if ($user !== '') {
            $this->smtpWrite($fp, "AUTH LOGIN\r\n");
            $this->smtpExpect($fp, '334');
            $this->smtpWrite($fp, base64_encode($user) . "\r\n");
            $this->smtpExpect($fp, '334');
            $this->smtpWrite($fp, base64_encode($pass) . "\r\n");
            $this->smtpExpect($fp, '235');
        }

        $this->smtpWrite($fp, 'MAIL FROM:<' . $from . ">\r\n");
        $this->smtpExpect($fp, '250');
        $this->smtpWrite($fp, 'RCPT TO:<' . $to . ">\r\n");
        $this->smtpExpect($fp, '250');
        $this->smtpWrite($fp, "DATA\r\n");
        $this->smtpExpect($fp, '354');

        $msg = "From: {$fromName} <{$from}>\r\nTo: <{$to}>\r\nSubject: {$subject}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$body}\r\n.\r\n";
        fwrite($fp, $msg);
        $this->smtpExpect($fp, '250');
        $this->smtpWrite($fp, "QUIT\r\n");
        fclose($fp);

        return true;
    }

    private function smtpWrite($fp, string $line): void
    {
        fwrite($fp, $line);
    }

    private function smtpReadMultiline($fp): string
    {
        $out = '';
        while ($line = fgets($fp, 515)) {
            $out .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        return $out;
    }

    private function smtpExpect($fp, string $code): void
    {
        $line = fgets($fp, 515);
        if ($line === false || ! str_starts_with(trim($line), $code)) {
            throw new \RuntimeException('SMTP unexpected: ' . (string) $line);
        }
    }

    private function encodeHeader(string $s): string
    {
        return '=?' . 'UTF-8' . '?B?' . base64_encode($s) . '?=';
    }
}
