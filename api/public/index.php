<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\PasswordController;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Repositories\PasswordResetRepository;
use App\Repositories\RefreshTokenRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Services\JwtService;
use App\Services\PasswordResetService;
use App\Services\RateLimitService;
use App\Services\GoogleAuthService;
use App\Controllers\GoogleAuthController;
use App\Controllers\TwoFactorAuthController;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$env = require __DIR__ . '/../src/Config/env.php';

// ── CORS: Origens permitidas ─────────────────────────────────────
$allowedOrigins = [
    'https://v0-app-nexora-bjj.vercel.app', // produção Vercel
    'http://localhost:3000',                 // desenvolvimento local
    'http://localhost:3001',
    'http://127.0.0.1:3000',
];

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($requestOrigin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$requestOrigin}");
} else {
    header('Access-Control-Allow-Origin: https://v0-app-nexora-bjj.vercel.app');
}

header('Vary: Origin');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = Database::connection();
$userRepo = new UserRepository($pdo);
$resetRepo = new PasswordResetRepository($pdo);
$refreshTokenRepo = new RefreshTokenRepository($pdo);
$jwt = new JwtService($env['jwt']['secret'], $env['jwt']['issuer'], $env['jwt']['expires_in']);
$rateLimit = new RateLimitService($env['security']['rate_limit_max_attempts'], $env['security']['rate_limit_window_seconds']);

$emailService = new EmailService($env['mail']);
$authController = new AuthController(new AuthService($userRepo, $jwt, $emailService, $env, $refreshTokenRepo), $userRepo, $rateLimit);
$passwordController = new PasswordController(
    new PasswordResetService($userRepo, $resetRepo, $emailService, $env),
    $rateLimit
);
$googleAuthController = new GoogleAuthController(
    new GoogleAuthService($env['google']),
    $userRepo,
    $jwt,
    $env
);
$twoFactorAuthController = new TwoFactorAuthController($userRepo);
$authMiddleware = new AuthMiddleware($jwt);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

try {
    if ($method === 'GET' && $path === '/') {
        Response::json([
            'name' => 'Nexora Auth API',
            'version' => '1.0.0',
            'status' => 'running'
        ]);
    } elseif ($method === 'GET' && $path === '/favicon.ico') {
        http_response_code(204);
        exit;
    } elseif ($method === 'GET' && $path === '/run-seed') {
        // ── Endpoint temporário de seed ─────────────────────────────
        // Protegido por SEED_TOKEN (variável de ambiente no Render)
        // Remover esta rota após criar o admin!
        $seedToken = $_ENV['SEED_TOKEN'] ?? null;
        $providedToken = $_GET['token'] ?? '';

        if (!$seedToken || !hash_equals($seedToken, $providedToken)) {
            http_response_code(403);
            Response::json(['error' => 'Token inválido ou ausente.']);
            exit;
        }

        $pdo = Database::connection();

        $name     = 'Administrador Nexora';
        $email    = 'admin@nexora.com';
        $password = 'Admin@123';
        $hash     = password_hash($password, PASSWORD_ARGON2ID);

        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => strtolower($email)]);
        $existing = $check->fetch();

        if ($existing) {
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, failed_login_attempts = 0, is_email_verified = 1 WHERE email = :email');
            $stmt->execute(['hash' => $hash, 'email' => strtolower($email)]);
            Response::json(['status' => 'updated', 'message' => 'Senha do admin atualizada com sucesso!', 'email' => $email, 'password' => $password]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, academy_name, is_email_verified, is_two_factor_enabled, failed_login_attempts) VALUES (:name, :email, :hash, :academy, 1, 0, 0)');
            $stmt->execute(['name' => $name, 'email' => strtolower($email), 'hash' => $hash, 'academy' => 'Nexora Headquarter']);
            $id = $pdo->lastInsertId();
            Response::json(['status' => 'created', 'message' => 'Admin criado com sucesso!', 'id' => $id, 'email' => $email, 'password' => $password]);
        }
        exit;

    } elseif ($method === 'GET' && $path === '/api-docs') {
        ob_start();
        try {
            $generator = new \OpenApi\Generator();
            $openapi = $generator->generate([__DIR__ . '/../src']);
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo $openapi->toJson();
        } catch (\Throwable $e) {
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'openapi' => '3.0.0',
                'info' => ['title' => 'Error', 'version' => '1.0.0'],
                'paths' => [],
                'error' => $e->getMessage()
            ]);
        }
        exit;
    } elseif ($method === 'GET' && $path === '/swagger') {
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="utf-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
          <title>Nexora Auth API - Swagger UI</title>
          <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
          <style>
            html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
            *, *:before, *:after { box-sizing: inherit; }
            body { margin: 0; background: #fafafa; }
          </style>
        </head>
        <body>
          <div id="swagger-ui"></div>
          <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" crossorigin></script>
          <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js" crossorigin></script>
          <script>
            window.onload = () => {
              window.ui = SwaggerUIBundle({
                url: '/api-docs',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                  SwaggerUIBundle.presets.apis,
                  SwaggerUIStandalonePreset
                ],
                plugins: [
                  SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout"
              });
            };
          </script>
        </body>
        </html>
        HTML;
        exit;
    } elseif ($method === 'POST' && $path === '/auth/signup') {
        $claims = $authMiddleware->authenticate(Request::bearerToken());
        $authController->signup($claims);
    } elseif ($method === 'POST' && $path === '/auth/login') {
        $authController->login();
    } elseif ($method === 'GET' && $path === '/auth/me') {
        $claims = $authMiddleware->authenticate(Request::bearerToken());
        $authController->me($claims);
    } elseif ($method === 'PUT' && $path === '/auth/me') {
        $claims = $authMiddleware->authenticate(Request::bearerToken());
        $authController->updateProfile($claims);
    } elseif ($method === 'POST' && $path === '/auth/logout') {
        $authController->logout();
    } elseif ($method === 'GET' && $path === '/auth/google') {
        $googleAuthController->login();
    } elseif ($method === 'GET' && $path === '/auth/google/callback') {
        $googleAuthController->callback();
    } elseif ($method === 'POST' && $path === '/auth/forgot-password') {
        $passwordController->forgotPassword();
    } elseif ($method === 'POST' && $path === '/auth/reset-password') {
        $passwordController->resetPassword();
    } elseif ($method === 'GET' && $path === '/auth/reset-password/validate') {
        $passwordController->validateResetToken();
    } elseif ($method === 'GET' && $path === '/auth/verify-email') {
        $authController->verifyEmail();
    } elseif ($method === 'POST' && $path === '/auth/refresh') {
        $authController->refresh();
    } elseif ($method === 'POST' && $path === '/auth/2fa/verify') {
        $claims = $authMiddleware->authenticate(Request::bearerToken());
        $authController->verify2fa($claims);
    } elseif ($method === 'POST' && $path === '/2fa/generate') {
        $claims = $authMiddleware->authenticate(Request::bearerToken());
        $twoFactorAuthController->generate($claims);
    } elseif ($method === 'POST' && $path === '/2fa/enable') {
        $claims = $authMiddleware->authenticate(Request::bearerToken());
        $twoFactorAuthController->enable($claims);
    } elseif ($method === 'POST' && $path === '/2fa/disable') {
        $claims = $authMiddleware->authenticate(Request::bearerToken());
        $twoFactorAuthController->disable($claims);
    } else {
        Response::error('NOT_FOUND', 'Endpoint não encontrado', [], 404);
    }
} catch (Throwable $e) {
    $status = 500;
    $code = 'UNEXPECTED_ERROR';

    $authErrors = [
        'Token ausente',
        'Token inválido',
        'Token expirado',
        'Usuário não encontrado',
        'Credenciais inválidas'
    ];

    if (in_array($e->getMessage(), $authErrors)) {
        $status = 401;
        $code = 'UNAUTHORIZED';
    }

    Response::error($code, $e->getMessage(), [], $status);
}
