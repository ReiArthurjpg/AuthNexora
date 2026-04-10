<?php

declare(strict_types=1);

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\PasswordController;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Services\JwtService;
use App\Services\PasswordResetService;
use App\Services\RateLimitService;

require_once __DIR__ . '/../vendor/autoload.php';

$env = require __DIR__ . '/../src/Config/env.php';

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = Database::connection();
$userRepo = new UserRepository($pdo);
$resetRepo = new PasswordResetRepository($pdo);
$jwt = new JwtService($env['jwt']['secret'], $env['jwt']['issuer'], $env['jwt']['expires_in']);
$rateLimit = new RateLimitService($env['security']['rate_limit_max_attempts'], $env['security']['rate_limit_window_seconds']);

$authController = new AuthController(new AuthService($userRepo, $jwt), $userRepo, $rateLimit);
$passwordController = new PasswordController(
    new PasswordResetService($userRepo, $resetRepo, new EmailService($env['mail']), $env),
    $rateLimit
);
$authMiddleware = new AuthMiddleware($jwt);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

try {
    if ($method === 'POST' && $path === '/auth/signup') {
        $authController->signup();
    } elseif ($method === 'POST' && $path === '/auth/login') {
        $authController->login();
    } elseif ($method === 'GET' && $path === '/auth/me') {
        $claims = $authMiddleware->authenticate(Request::bearerToken());
        $authController->me($claims);
    } elseif ($method === 'POST' && $path === '/auth/logout') {
        $authController->logout();
    } elseif ($method === 'POST' && $path === '/auth/forgot-password') {
        $passwordController->forgotPassword();
    } elseif ($method === 'POST' && $path === '/auth/reset-password') {
        $passwordController->resetPassword();
    } elseif ($method === 'GET' && $path === '/auth/reset-password/validate') {
        $passwordController->validateResetToken();
    } else {
        Response::error('NOT_FOUND', 'Endpoint não encontrado', [], 404);
    }
} catch (Throwable $e) {
    Response::error('UNEXPECTED_ERROR', $e->getMessage(), [], 500);
}
