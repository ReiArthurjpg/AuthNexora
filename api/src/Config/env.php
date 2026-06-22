<?php

declare(strict_types=1);

$configuredOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? $_ENV['ALLOWED_ORIGINS'] ?? $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
$normalizedOrigins = preg_replace('/(https?:\/\/[^,\s;]+)(https?:\/\/)/', '$1,$2', $configuredOrigins ?? '');
$splitOrigins = preg_split('/[\s,;]+/', (string) $normalizedOrigins) ?: [];

return [
    'app' => [
        'base_url' => $_ENV['APP_BASE_URL'] ?? 'http://localhost:8080',
        'frontend_url' => $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000',
        'frontend_reset_url' => $_ENV['FRONTEND_RESET_URL'] ?? 'http://localhost:3000/reset-password',
        'frontend_verify_email_url' => $_ENV['FRONTEND_VERIFY_EMAIL_URL'] ?? 'http://localhost:3000/verify-email',
        'cors_allowed_origins' => array_values(array_filter(array_map('trim', $splitOrigins))),
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'],
        'port' => $_ENV['DB_PORT'],
        'database' => $_ENV['DB_DATABASE'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'],
        'charset' => 'utf8mb4',
    ],
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'change-me',
        'issuer' => $_ENV['JWT_ISSUER'] ?? 'authnexora-api',
        'expires_in' => (int) ($_ENV['JWT_EXPIRES_IN'] ?? 3600),
    ],
    'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => (int) ($_ENV['MAIL_PORT'] ?? 465),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => 'ssl',
        'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? '',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Nexora',
    ],
    'security' => [
        'reset_token_ttl_minutes' => 30,
        'rate_limit_max_attempts' => 5,
        'rate_limit_window_seconds' => 60,
    ],
    'google' => [
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri' => ($_ENV['APP_BASE_URL'] ?? 'http://localhost:8080') . '/auth/google/callback',
    ],
];
