<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Config;
use PDO;

final class AttachmentService
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    /**
     * @return array{id:int,stored:string,thumb:?string}
     */
    public function attachUpload(int $userId, int $transactionId, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Upload failed.');
        }
        $max = (int) Config::get('app.upload_max_mb', 10) * 1024 * 1024;
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $max) {
            throw new \InvalidArgumentException('File too large.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || ! is_uploaded_file($tmp)) {
            throw new \InvalidArgumentException('Invalid upload.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        if (! isset(self::ALLOWED[$mime])) {
            throw new \InvalidArgumentException('File type not allowed (JPG, PNG, PDF).');
        }

        $pdo = Database::pdo();
        $ok = $pdo->prepare('SELECT id FROM transactions WHERE id = ? AND user_id = ? AND deleted_at IS NULL LIMIT 1');
        $ok->execute([$transactionId, $userId]);
        if (! $ok->fetchColumn()) {
            throw new \InvalidArgumentException('Transaction not found.');
        }

        $ext = self::ALLOWED[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = dirname(__DIR__, 2) . '/public/uploads/transactions/' . $userId;
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $dest = $dir . '/' . $name;
        if (! move_uploaded_file($tmp, $dest)) {
            throw new \RuntimeException('Could not store file.');
        }

        $thumb = null;
        if ($mime !== 'application/pdf' && function_exists('imagecreatetruecolor')) {
            $thumb = 'thumb_' . $name;
            $this->makeThumb($dest, $dir . '/' . $thumb, $mime);
        }

        $pdo->prepare(
            'INSERT INTO transaction_attachments (transaction_id, stored_filename, original_name, mime_type, size_bytes) VALUES (?,?,?,?,?)'
        )->execute([
            $transactionId,
            $userId . '/' . $name,
            (string) ($file['name'] ?? 'file'),
            $mime,
            $size,
        ]);
        $id = (int) $pdo->lastInsertId();

        AuditLogger::log('attachment_upload', $userId, 'transaction_attachment', (string) $id);

        return ['id' => $id, 'stored' => $name, 'thumb' => $thumb];
    }

    public function deleteAttachment(int $userId, int $attachmentId): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT a.*, t.user_id AS uid FROM transaction_attachments a
             JOIN transactions t ON t.id = a.transaction_id
             WHERE a.id = ? LIMIT 1'
        );
        $stmt->execute([$attachmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $row || (int) $row['uid'] !== $userId) {
            throw new \InvalidArgumentException('Not found.');
        }
        $pdo->prepare('DELETE FROM transaction_attachments WHERE id = ?')->execute([$attachmentId]);
        $base = dirname(__DIR__, 2) . '/public/uploads/transactions/' . $row['stored_filename'];
        if (is_file($base)) {
            @unlink($base);
        }
        $thumbPath = dirname($base) . '/thumb_' . basename($base);
        if (is_file($thumbPath)) {
            @unlink($thumbPath);
        }
        AuditLogger::log('attachment_delete', $userId, 'transaction_attachment', (string) $attachmentId);
    }

    private function makeThumb(string $src, string $dest, string $mime): void
    {
        $maxW = 200;
        $maxH = 200;
        if ($mime === 'image/jpeg') {
            $im = @imagecreatefromjpeg($src);
        } elseif ($mime === 'image/png') {
            $im = @imagecreatefrompng($src);
        } else {
            return;
        }
        if (! $im) {
            return;
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $scale = min($maxW / max($w, 1), $maxH / max($h, 1), 1);
        $nw = (int) max(1, round($w * $scale));
        $nh = (int) max(1, round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        if ($mime === 'image/png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        if ($mime === 'image/jpeg') {
            imagejpeg($dst, $dest, 85);
        } else {
            imagepng($dst, $dest, 6);
        }
        imagedestroy($im);
        imagedestroy($dst);
    }
}
