<?php

declare(strict_types=1);

return [
    'app' => [
        'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8080',
        'frontend_reset_url' => getenv('FRONTEND_RESET_URL') ?: 'http://localhost:3000/reset-password',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'authnexora',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'change-me',
        'issuer' => getenv('JWT_ISSUER') ?: 'authnexora-api',
        'expires_in' => (int) (getenv('JWT_EXPIRES_IN') ?: 3600),
    ],
    'mail' => [
        'host' => getenv('MAIL_HOST') ?: 'smtp.mailtrap.io',
        'port' => (int) (getenv('MAIL_PORT') ?: 587),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'no-reply@nexora.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Nexora',
    ],
    'security' => [
        'reset_token_ttl_minutes' => 30,
        'rate_limit_max_attempts' => 5,
        'rate_limit_window_seconds' => 60,
    ],
];
