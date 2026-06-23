<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\RateLimitService;
use RuntimeException;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly UserRepository $users,
        private readonly RateLimitService $rateLimit
    ) {
    }

    public function signup(array $claims = []): void
    {
        $body = Request::jsonBody();
        $errors = $this->validateSignup($body);

        if ($errors) {
            Response::error('VALIDATION_ERROR', 'Dados inválidos', $errors, 422);
            return;
        }

        if ($this->users->findByEmail($body['email'])) {
            Response::error('EMAIL_ALREADY_EXISTS', 'E-mail já cadastrado', [], 409);
            return;
        }

        $createdBy = isset($claims['sub']) ? (int) $claims['sub'] : null;
        $user = $this->auth->signup($body, $createdBy);
        Response::json(['message' => 'Usuário criado com sucesso', 'user' => $user], 201);
    }

    public function login(): void
    {
        if (!$this->rateLimit->hit('login:' . Request::ip())) {
            Response::error('RATE_LIMIT', 'Muitas tentativas. Tente novamente.', [], 429);
            return;
        }

        $body = Request::jsonBody();
        $errors = [];
        if (empty($body['email'])) {
            $errors['email'][] = 'E-mail é obrigatório';
        }
        if (empty($body['password'])) {
            $errors['password'][] = 'Senha é obrigatória';
        }

        if ($errors) {
            Response::error('VALIDATION_ERROR', 'Dados inválidos', $errors, 422);
            return;
        }

        try {
            $data = $this->auth->login($body['email'], $body['password']);
            if (!$data) {
                Response::error('INVALID_CREDENTIALS', 'Credenciais inválidas', [], 401);
                return;
            }
            Response::json($data);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'ACCOUNT_LOCKED') {
                Response::error('ACCOUNT_LOCKED', 'Sua conta foi bloqueada devido a muitas tentativas incorretas. Por favor, redefina sua senha.', [], 403);
                return;
            }
            throw $e;
        }
    }

    public function me(array $claims): void
    {
        $userId = (int) ($claims['sub'] ?? 0);
        $user = $this->users->findById($userId);
        if (!$user) {
            throw new RuntimeException('Usuário não encontrado');
        }

        Response::json($user);
    }

    public function logout(): void
    {
        $body = Request::jsonBody();
        $refreshToken = $body['refreshToken'] ?? null;
        
        $this->auth->logout($refreshToken);
        
        Response::json(['message' => 'Logout realizado com sucesso']);
    }

    public function refresh(): void
    {
        $body = Request::jsonBody();
        if (empty($body['refreshToken'])) {
            Response::error('MISSING_TOKEN', 'Refresh token ausente', [], 400);
            return;
        }

        try {
            $data = $this->auth->refreshToken($body['refreshToken']);
            Response::json($data);
        } catch (RuntimeException $e) {
            Response::error('INVALID_TOKEN', $e->getMessage(), [], 401);
        }
    }

    public function updateProfile(array $claims): void
    {
        $userId = (int) ($claims['sub'] ?? 0);
        $body = Request::jsonBody();

        if (empty($body['name'])) {
            Response::error('VALIDATION_ERROR', 'Nome é obrigatório', ['name' => ['Nome é obrigatório']], 422);
            return;
        }

        $body['updated_by'] = $userId;
        $this->users->update($userId, $body);
        $updatedUser = $this->users->findById($userId);

        Response::json(['message' => 'Perfil atualizado com sucesso', 'user' => $updatedUser]);
    }

    private function validateSignup(array $body): array
    {
        $errors = [];

        if (empty($body['name']) || mb_strlen((string) $body['name']) < 3) {
            $errors['name'][] = 'Nome deve ter ao menos 3 caracteres';
        }
        if (empty($body['email']) || !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'E-mail profissional inválido';
        }
        if (empty($body['academy_name']) || mb_strlen((string) $body['academy_name']) < 2) {
            $errors['academy_name'][] = 'Nome da academia deve ter ao menos 2 caracteres';
        }
        if (empty($body['password']) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $body['password'])) {
            $errors['password'][] = 'Senha deve conter 8+ caracteres, maiúscula, minúscula, número e símbolo';
        }
        if (($body['password'] ?? null) !== ($body['confirmPassword'] ?? null)) {
            $errors['confirmPassword'][] = 'As senhas não coincidem';
        }

        // Novos campos (opcionais na validação básica, mas aceitos)
        // Você pode adicionar regras específicas aqui se desejar (ex: validar formato de CPF)

        return $errors;
    }

    public function verify2fa(array $claims): void
    {
        if (($claims['scope'] ?? '') !== '2fa') {
            Response::error('INVALID_TOKEN', 'Token inválido para esta operação', [], 401);
            return;
        }

        $userId = (int) ($claims['sub'] ?? 0);
        $user = $this->users->findByEmail($this->users->findById($userId)['email']);
        
        $body = Request::jsonBody();
        if (empty($body['code'])) {
            Response::error('VALIDATION_ERROR', 'Código 2FA é obrigatório', [], 422);
            return;
        }

        $tfa = new \RobThree\Auth\TwoFactorAuth(new \App\Providers\ChillerlanQRCodeProvider(), 'AuthNexora');
        if (!$tfa->verifyCode($user['two_factor_secret'], $body['code'])) {
            Response::error('INVALID_CODE', 'Código 2FA inválido', [], 401);
            return;
        }

        $data = $this->auth->issueTokenForUser($user);
        Response::json($data);
    }

    public function verifyEmail(): void
    {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            Response::error('MISSING_TOKEN', 'Token de verificação ausente', [], 400);
            return;
        }

        try {
            $claims = (new \App\Services\JwtService(
                (require __DIR__ . '/../Config/env.php')['jwt']['secret'],
                (require __DIR__ . '/../Config/env.php')['jwt']['issuer'],
                (require __DIR__ . '/../Config/env.php')['jwt']['expires_in']
            ))->decodeToken($token);

            if (($claims['scope'] ?? '') !== 'email_verification') {
                throw new \RuntimeException('Token inválido');
            }

            $userId = (int) ($claims['user_id'] ?? 0);
            $user = $this->users->findById($userId);

            if (!$user) {
                throw new \RuntimeException('Usuário não encontrado');
            }

            if (!$user['is_email_verified']) {
                $this->users->verifyEmail($userId);
            }

            Response::json(['message' => 'E-mail verificado com sucesso']);
        } catch (\Throwable $e) {
            Response::error('INVALID_TOKEN', 'Token inválido ou expirado', [], 401);
        }
    }
}
