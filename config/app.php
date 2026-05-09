<?php

declare(strict_types=1);

return [
    'name' => getenv('APP_NAME') ?: 'KHFinaM',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim(getenv('APP_URL') ?: 'http://localhost', '/'),
    'timezone' => 'Asia/Kuala_Lumpur',
    'locale' => getenv('DEFAULT_LOCALE') ?: 'en',
    'base_currency' => getenv('BASE_CURRENCY') ?: 'MYR',
    'session_lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 120),
    'session_secure' => filter_var(getenv('SESSION_SECURE_COOKIE') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'csrf_key' => getenv('CSRF_TOKEN_KEY') ?: '_csrf_token',
    'upload_max_mb' => (int) (getenv('UPLOAD_MAX_MB') ?: 10),
    'upload_allowed_mime' => array_filter(array_map('trim', explode(',', getenv('ALLOWED_UPLOAD_MIME') ?: 'image/jpeg,image/png,application/pdf'))),
    'rate_limit' => [
        'login_max' => (int) (getenv('RATE_LIMIT_LOGIN_MAX') ?: 5),
        'login_window' => (int) (getenv('RATE_LIMIT_LOGIN_WINDOW') ?: 900),
        'api_max' => (int) (getenv('RATE_LIMIT_API_MAX') ?: 100),
        'api_window' => (int) (getenv('RATE_LIMIT_API_WINDOW') ?: 60),
    ],
    'mail_from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@localhost',
    'mail_from_name' => getenv('MAIL_FROM_NAME') ?: 'KHFinaM',
    'cron_key' => getenv('CRON_KEY') ?: '',
    'paths' => [
        'root' => dirname(__DIR__),
        'public' => dirname(__DIR__) . '/public',
        'storage' => dirname(__DIR__) . '/storage',
        'uploads' => dirname(__DIR__) . '/public/uploads',
        'logs' => dirname(__DIR__) . '/logs',
    ],
];
