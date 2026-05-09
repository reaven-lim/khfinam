<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Auth;
use App\Repositories\TransactionRepository;
use App\Services\ReportPdfService;

final class ReportApiController
{
    public function csvSummary(): void
    {
        if (! Auth::check() || ! Auth::isSuperAdmin()) {
            http_response_code(403);
            echo 'Forbidden';

            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->query(
            'SELECT transaction_date, type, title, amount_base, user_id FROM transactions WHERE deleted_at IS NULL AND parent_transaction_id IS NULL ORDER BY transaction_date DESC'
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions-summary.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['transaction_date', 'type', 'title', 'amount_base', 'user_id']);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    public function pdfMonthly(): void
    {
        if (! Auth::check() || ! Auth::isSuperAdmin()) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        $from = Request::query('from');
        $to = Request::query('to');
        $pdf = (new ReportPdfService())->monthlySummaryPdf($from ?: null, $to ?: null);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="khfinam-report.pdf"');
        echo $pdf;
        exit;
    }

    public function heatmap(): void
    {
        if (! Auth::check()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
        $year = (int) (Request::query('year') ?? date('Y'));
        if (Auth::isSuperAdmin() && Request::query('user_id') !== null) {
            $uid = (int) Request::query('user_id');
        } else {
            $uid = (int) Auth::id();
        }
        $data = (new TransactionRepository())->heatmapExpensesForUser($uid, $year);
        Response::json(['year' => $year, 'expenses_by_date' => $data]);
    }
}
