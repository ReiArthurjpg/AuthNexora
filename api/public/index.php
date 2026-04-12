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
use App\Services\GoogleAuthService;
use App\Controllers\GoogleAuthController;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$env = require __DIR__ . '/../src/Config/env.php';

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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
    } elseif ($method === 'GET' && $path === '/api-docs') {
        $generator = new \OpenApi\Generator();
        $openapi = $generator->generate([__DIR__ . '/../src']);
        header('Content-Type: application/json; charset=utf-8');
        echo $openapi->toJson();
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
    }
    
    // Bootstrapping para rotas que precisam de banco e dependências
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
    $googleAuthController = new GoogleAuthController(
        new GoogleAuthService($env['google']),
        $userRepo,
        $jwt
    );
    $authMiddleware = new AuthMiddleware($jwt);

    if ($method === 'POST' && $path === '/auth/signup') {
        $authController->signup();
    } elseif ($method === 'POST' && $path === '/auth/login') {
        $authController->login();
    } elseif ($method === 'GET' && $path === '/auth/me') {
        $claims = $authMiddleware->authenticate(Request::bearerToken());
        $authController->me($claims);
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

    Response::error($code, $e->getMessage() . ' no arquivo ' . $e->getFile() . ' linha ' . $e->getLine(), [], $status);
}
