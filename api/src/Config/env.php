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
        'host' => 'smtp.gmail.com',
        'port' => 465,
        'username' => 'arthurnexora@gmail.com',
        'password' => 'ufwtqcyhroqbxaxa',
        'encryption' => 'ssl',
        'from_email' => 'arthurnexora@gmail.com',
        'from_name' => 'Nexora',
    ],
    'security' => [
        'reset_token_ttl_minutes' => 30,
        'rate_limit_max_attempts' => 5,
        'rate_limit_window_seconds' => 60,
    ],
    'google' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => (getenv('APP_BASE_URL') ?: 'http://localhost:8080') . '/auth/google/callback',
    ],
];
