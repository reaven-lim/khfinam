<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\Str;
use App\Helpers\Url;
use App\Helpers\View;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use PDO;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Response::redirect(Url::to(Auth::isSuperAdmin() ? '/admin' : '/app'));

            return;
        }
        View::renderLayout('guest', 'auth/login', [
            'title' => 'Sign in',
            'error' => \App\Core\Session::getFlash('error'),
        ]);
    }

    public function login(): void
    {
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            \App\Core\Session::flash('error', 'Invalid session. Please try again.');
            Response::redirect(Url::to('/login'));
        }

        $login = trim((string) (Request::post()['login'] ?? ''));
        $password = (string) (Request::post()['password'] ?? '');
        $remember = ! empty(Request::post()['remember']);

        $ip = Request::ip();
        $rl = Config::get('app.rate_limit');
        if (RateLimiter::tooMany('login:' . $ip, (int) $rl['login_max'], (int) $rl['login_window'])) {
            \App\Core\Session::flash('error', 'Too many attempts. Please wait and try again.');
            AuditLogger::log('login_rate_limited', null, 'auth', null, ['ip' => $ip]);
            Response::redirect(Url::to('/login'));
        }

        $repo = new UserRepository();
        $user = $repo->findByUsernameOrEmail($login);
        if ($user === null) {
            \App\Core\Session::flash('error', 'Invalid credentials.');
            AuditLogger::log('login_failed', null, 'auth', null, ['reason' => 'user_not_found', 'login' => $login]);
            Response::redirect(Url::to('/login'));
        }

        if (! empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) {
            \App\Core\Session::flash('error', 'Account temporarily locked. Try again later.');
            Response::redirect(Url::to('/login'));
        }

        if (! password_verify($password, (string) $user['password_hash'])) {
            $repo->incrementFailedLogin((int) $user['id']);
            if ((int) $user['failed_login_attempts'] + 1 >= 8) {
                $repo->lockAccount((int) $user['id'], new \DateTime('+30 minutes'));
            }
            AuditLogger::log('login_failed', (int) $user['id'], 'auth', null, ['reason' => 'bad_password']);
            \App\Core\Session::flash('error', 'Invalid credentials.');
            Response::redirect(Url::to('/login'));
        }

        Auth::login((int) $user['id']);
        $repo->updateLastLogin((int) $user['id']);

        if ($remember) {
            $token = Str::random(32);
            $repo->setRememberToken((int) $user['id'], hash('sha256', $token), (new \DateTime('+30 days'))->format('Y-m-d H:i:s'));
            setcookie('remember_token', $token, [
                'expires' => time() + 30 * 86400,
                'path' => '/',
                'secure' => (bool) Config::get('app.session_secure'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        AuditLogger::log('login_success', (int) $user['id'], 'user', (string) $user['id']);

        $role = $user['role'] ?? 'user';
        Response::redirect(Url::to($role === 'super_admin' ? '/admin' : '/app'));
    }

    public function logout(): void
    {
        if (Auth::check()) {
            $uid = Auth::id();
            AuditLogger::log('logout', $uid, 'user', (string) $uid);
            $repo = new UserRepository();
            if ($uid) {
                $repo->setRememberToken($uid, null, null);
            }
        }
        if (! empty($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
        }
        Auth::logout();
        Response::redirect(Url::to('/login'));
    }

    public function showForgot(): void
    {
        View::renderLayout('guest', 'auth/forgot', [
            'title' => 'Forgot password',
            'message' => \App\Core\Session::getFlash('message'),
            'error' => \App\Core\Session::getFlash('error'),
        ]);
    }

    public function forgotSubmit(): void
    {
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            \App\Core\Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/forgot-password'));
        }
        $email = trim((string) (Request::post()['email'] ?? ''));
        $repo = new UserRepository();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $raw = Str::random(40);
            $hash = hash('sha256', $raw);
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([(int) $row['id']]);
            $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                ->execute([(int) $row['id'], $hash]);
            $link = Url::to('/reset-password?token=' . urlencode($raw));
            // Email via MailService if SMTP configured — always show generic message
            self::trySendResetEmail($email, $link);
        }
        \App\Core\Session::flash('message', 'If an account exists for that email, reset instructions were sent.');
        Response::redirect(Url::to('/forgot-password'));
    }

    public function showReset(): void
    {
        $token = Request::query('token');
        if ($token === null || $token === '') {
            Response::redirect(Url::to('/login'));
        }
        View::renderLayout('guest', 'auth/reset', [
            'title' => 'Reset password',
            'token' => $token,
            'error' => \App\Core\Session::getFlash('error'),
        ]);
    }

    public function resetSubmit(): void
    {
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            \App\Core\Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/login'));
        }
        $token = (string) (Request::post()['token'] ?? '');
        $pass = (string) (Request::post()['password'] ?? '');
        $pass2 = (string) (Request::post()['password_confirm'] ?? '');
        if (strlen($pass) < 8 || $pass !== $pass2) {
            \App\Core\Session::flash('error', 'Passwords must match and be at least 8 characters.');
            Response::redirect(Url::to('/reset-password?token=' . urlencode($token)));
        }
        $hash = hash('sha256', $token);
        $pdo = Database::pdo();
        $q = $pdo->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW() LIMIT 1');
        $q->execute([$hash]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (! $row) {
            \App\Core\Session::flash('error', 'Invalid or expired reset link.');
            Response::redirect(Url::to('/login'));
        }
        $uid = (int) $row['user_id'];
        $repo = new UserRepository();
        $repo->updatePassword($uid, password_hash($pass, PASSWORD_DEFAULT));
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$uid]);
        AuditLogger::log('password_reset', $uid, 'user', (string) $uid);
        \App\Core\Session::flash('message', 'Password updated. You can sign in now.');
        Response::redirect(Url::to('/login'));
    }

    private static function trySendResetEmail(string $to, string $link): void
    {
        try {
            $svc = new \App\Services\MailService();
            $svc->send($to, 'Password reset', 'Reset your password: ' . $link);
        } catch (\Throwable) {
        }
    }
}
