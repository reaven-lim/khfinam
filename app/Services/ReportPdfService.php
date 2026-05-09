<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Config;
use PDO;

final class ReportPdfService
{
    public function monthlySummaryPdf(?string $from, ?string $to): string
    {
        if (! class_exists(\TCPDF::class, false)) {
            require_once dirname(__DIR__, 2) . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        }
        $pdo = Database::pdo();
        $sql = "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS ym,
                       SUM(CASE WHEN type='income' AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS inc,
                       SUM(CASE WHEN type='expense' AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS exp
                FROM transactions WHERE deleted_at IS NULL AND parent_transaction_id IS NULL ";
        $params = [];
        if ($from) {
            $sql .= ' AND transaction_date >= ?';
            $params[] = $from;
        }
        if ($to) {
            $sql .= ' AND transaction_date <= ?';
            $params[] = $to;
        }
        $sql .= ' GROUP BY ym ORDER BY ym ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new \TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator('KHFinaM');
        $pdf->SetTitle('Financial summary');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Write(0, Config::get('app.name', 'KHFinaM') . " — summary report\n\n", '', 0, 'L', true, 0, false, false, 0);

        $html = '<table border="1" cellpadding="4"><thead><tr style="background-color:#0f766e;color:#fff;"><th>Month</th><th align="right">Income</th><th align="right">Expense</th><th align="right">Net</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $inc = (float) $r['inc'];
            $exp = (float) $r['exp'];
            $net = $inc - $exp;
            $html .= '<tr><td>' . htmlspecialchars((string) $r['ym'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td align="right">' . number_format($inc, 2) . '</td>'
                . '<td align="right">' . number_format($exp, 2) . '</td>'
                . '<td align="right">' . number_format($net, 2) . '</td></tr>';
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('report.pdf', 'S');
    }

    /** Monthly rollup for a single user (base currency). */
    public function monthlySummaryPdfForUser(int $userId, ?string $from, ?string $to): string
    {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid user.');
        }
        if (! class_exists(\TCPDF::class, false)) {
            require_once dirname(__DIR__, 2) . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        }
        $pdo = Database::pdo();
        $sql = "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS ym,
                       SUM(CASE WHEN type='income' AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS inc,
                       SUM(CASE WHEN type='expense' AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS exp
                FROM transactions
                WHERE user_id = ? AND deleted_at IS NULL AND parent_transaction_id IS NULL ";
        $params = [$userId];
        if ($from) {
            $sql .= ' AND transaction_date >= ?';
            $params[] = $from;
        }
        if ($to) {
            $sql .= ' AND transaction_date <= ?';
            $params[] = $to;
        }
        $sql .= ' GROUP BY ym ORDER BY ym ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new \TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator('KHFinaM');
        $pdf->SetTitle('Financial summary');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Write(0, Config::get('app.name', 'KHFinaM') . " — personal summary report\n\n", '', 0, 'L', true, 0, false, false, 0);

        $html = '<table border="1" cellpadding="4"><thead><tr style="background-color:#0f766e;color:#fff;"><th>Month</th><th align="right">Income</th><th align="right">Expense</th><th align="right">Net</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $inc = (float) $r['inc'];
            $exp = (float) $r['exp'];
            $net = $inc - $exp;
            $html .= '<tr><td>' . htmlspecialchars((string) $r['ym'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td align="right">' . number_format($inc, 2) . '</td>'
                . '<td align="right">' . number_format($exp, 2) . '</td>'
                . '<td align="right">' . number_format($net, 2) . '</td></tr>';
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('report.pdf', 'S');
    }
}
