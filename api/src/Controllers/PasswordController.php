<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\PasswordResetService;
use App\Services\RateLimitService;

final class PasswordController
{
    public function __construct(
        private readonly PasswordResetService $passwordReset,
        private readonly RateLimitService $rateLimit
    ) {
    }

    public function forgotPassword(): void
    {
        if (!$this->rateLimit->hit('forgot:' . Request::ip())) {
            Response::error('RATE_LIMIT', 'Muitas tentativas. Tente novamente.', [], 429);
            return;
        }

        $body = Request::jsonBody();
        if (empty($body['email']) || !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('VALIDATION_ERROR', 'Dados inválidos', ['email' => ['Formato inválido']], 422);
            return;
        }

        $this->passwordReset->request($body['email']);
        Response::json(['message' => 'Se o e-mail existir, enviaremos instruções para redefinição.']);
    }

    public function validateResetToken(): void
    {
        $token = $_GET['token'] ?? '';
        if (!$token || !$this->passwordReset->validate($token)) {
            Response::json(['valid' => false, 'message' => 'Token inválido ou expirado'], 400);
            return;
        }

        Response::json(['valid' => true]);
    }

    public function resetPassword(): void
    {
        $body = Request::jsonBody();
        $errors = [];

        if (empty($body['token'])) {
            $errors['token'][] = 'Token é obrigatório';
        }
        if (empty($body['newPassword']) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $body['newPassword'])) {
            $errors['newPassword'][] = 'Senha fora da política';
        }
        if (($body['newPassword'] ?? null) !== ($body['confirmPassword'] ?? null)) {
            $errors['confirmPassword'][] = 'As senhas não coincidem';
        }

        if ($errors) {
            Response::error('VALIDATION_ERROR', 'Dados inválidos', $errors, 422);
            return;
        }

        $ok = $this->passwordReset->reset($body['token'], $body['newPassword']);
        if (!$ok) {
            Response::error('INVALID_TOKEN', 'Token inválido/expirado/usado', [], 400);
            return;
        }

        Response::json(['message' => 'Senha alterada com sucesso']);
    }
}
