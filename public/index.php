<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Core\Session;
use App\Helpers\Auth;
use App\Repositories\UserRepository;

Session::start();
Session::enforceIdleTimeout();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header(
    "Content-Security-Policy: default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; " .
    "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com; " .
    "font-src https://fonts.gstatic.com 'self'; img-src 'self' data: blob:; connect-src 'self'"
);

if (! Auth::check() && ! empty($_COOKIE['remember_token'])) {
    $u = (new UserRepository())->findByRememberToken((string) $_COOKIE['remember_token']);
    if ($u) {
        Auth::login((int) $u['id']);
    }
}

$routes = require dirname(__DIR__) . '/routes/web.php';
Router::dispatch($routes);
