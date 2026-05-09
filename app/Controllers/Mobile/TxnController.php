<?php

declare(strict_types=1);

namespace App\Controllers\Mobile;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\Url;
use App\Helpers\View;
use App\Repositories\CategoryRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\AttachmentService;
use App\Services\TransactionService;

final class TxnController
{
    public function show(string $id): void
    {
        $this->requireUser();
        $tid = (int) $id;
        $uid = (int) Auth::id();
        $repo = new TransactionRepository();
        $tx = $repo->findByIdForUser($tid, $uid);
        if (! $tx) {
            Response::redirect(Url::to('/app'));

            return;
        }
        $children = [];
        if (! empty($tx['is_consolidated_parent'])) {
            $children = $repo->childrenForParent($tid, $uid);
        }
        $pdo = \App\Core\Database::pdo();
        $tags = $pdo->prepare('SELECT tag FROM transaction_tags WHERE transaction_id = ?');
        $tags->execute([$tid]);
        $tagList = $tags->fetchAll(\PDO::FETCH_COLUMN);
        $att = $pdo->prepare(
            'SELECT * FROM transaction_attachments WHERE transaction_id = ? ORDER BY id DESC'
        );
        $att->execute([$tid]);
        $attachments = $att->fetchAll(\PDO::FETCH_ASSOC);

        $wallets = (new WalletRepository())->forUser($uid, false);
        $cr = new CategoryRepository();
        View::renderLayout('mobile', 'mobile/transaction_show', [
            'title' => $tx['title'],
            'tx' => $tx,
            'children' => $children,
            'tags' => $tagList,
            'attachments' => $attachments,
            'wallets' => $wallets,
            'categoriesIncome' => $cr->forUserIncludingGlobal($uid, 'income'),
            'categoriesExpense' => $cr->forUserIncludingGlobal($uid, 'expense'),
            'error' => Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireUser();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/app/transaction/' . $id));
        }
        try {
            (new TransactionService())->updateForUser((int) Auth::id(), (int) $id, [
                'title' => Request::post()['title'] ?? '',
                'amount' => (float) (Request::post()['amount'] ?? 0),
                'wallet_id' => (int) (Request::post()['wallet_id'] ?? 0),
                'category_id' => (int) (Request::post()['category_id'] ?? 0),
                'from_wallet_id' => (int) (Request::post()['from_wallet_id'] ?? 0),
                'to_wallet_id' => (int) (Request::post()['to_wallet_id'] ?? 0),
                'transaction_date' => Request::post()['transaction_date'] ?? '',
                'type' => Request::post()['type'] ?? '',
                'notes' => Request::post()['notes'] ?? '',
                'tags' => array_filter(array_map('trim', explode(',', (string) (Request::post()['tags'] ?? '')))),
            ]);
            Response::redirect(Url::to('/app/transaction/' . $id));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::to('/app/transaction/' . $id));
        }
    }

    public function destroy(string $id): void
    {
        $this->requireUser();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/app/transaction/' . $id));
        }
        try {
            (new TransactionService())->softDeleteForUser((int) Auth::id(), (int) $id);
            Response::redirect(Url::to('/app'));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::to('/app/transaction/' . $id));
        }
    }

    public function attach(string $id): void
    {
        $this->requireUser();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/app/transaction/' . $id));
        }
        $file = $_FILES['file'] ?? null;
        if (! is_array($file)) {
            Session::flash('error', 'Choose a file.');
            Response::redirect(Url::to('/app/transaction/' . $id));
        }
        try {
            (new AttachmentService())->attachUpload((int) Auth::id(), (int) $id, $file);
            Response::redirect(Url::to('/app/transaction/' . $id));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::to('/app/transaction/' . $id));
        }
    }

    public function attachDeletePost(string $id): void
    {
        $this->requireUser();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Response::redirect(Url::to('/app/transaction/' . $id));

            return;
        }
        $aid = (int) (Request::post()['attachment_id'] ?? 0);
        try {
            (new AttachmentService())->deleteAttachment((int) Auth::id(), $aid);
        } catch (\Throwable) {
        }
        Response::redirect(Url::to('/app/transaction/' . $id));
    }

    public function addChild(string $id): void
    {
        $this->requireUser();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/app/transaction/' . $id));
        }
        try {
            $svc = new TransactionService();
            $svc->createForUser((int) Auth::id(), [
                'type' => (string) (Request::post()['type'] ?? 'expense'),
                'title' => trim((string) (Request::post()['title'] ?? '')),
                'amount' => (float) (Request::post()['amount'] ?? 0),
                'wallet_id' => (int) (Request::post()['wallet_id'] ?? 0),
                'category_id' => (int) (Request::post()['category_id'] ?? 0),
                'transaction_date' => (string) (Request::post()['transaction_date'] ?? date('Y-m-d')),
                'notes' => trim((string) (Request::post()['notes'] ?? '')),
                'parent_transaction_id' => (int) $id,
            ]);
            Response::redirect(Url::to('/app/transaction/' . $id));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::to('/app/transaction/' . $id));
        }
    }

    private function requireUser(): void
    {
        if (! Auth::check()) {
            Response::redirect(Url::to('/login'));
        }
        if (Auth::isSuperAdmin()) {
            Response::redirect(Url::to('/admin'));
        }
    }
}
