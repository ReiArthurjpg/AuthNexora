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

    public function signup(): void
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

        $user = $this->auth->signup($body['name'], $body['email'], $body['password'], $body['academyName'] ?? null);
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

        $data = $this->auth->login($body['email'], $body['password']);
        if (!$data) {
            Response::error('INVALID_CREDENTIALS', 'Credenciais inválidas', [], 401);
            return;
        }

        Response::json($data);
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
        Response::json(['message' => 'Logout realizado com sucesso']);
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
        if (empty($body['academyName']) || mb_strlen((string) $body['academyName']) < 2) {
            $errors['academyName'][] = 'Nome da academia deve ter ao menos 2 caracteres';
        }
        if (empty($body['password']) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $body['password'])) {
            $errors['password'][] = 'Senha deve conter 8+ caracteres, maiúscula, minúscula, número e símbolo';
        }
        if (($body['password'] ?? null) !== ($body['confirmPassword'] ?? null)) {
            $errors['confirmPassword'][] = 'As senhas não coincidem';
        }

        return $errors;
    }
}
