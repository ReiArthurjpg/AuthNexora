<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use DateInterval;
use DateTimeImmutable;

final class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordResetRepository $resets,
        private readonly EmailService $email,
        private readonly array $env
    ) {
    }

    public function request(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT' . $this->env['security']['reset_token_ttl_minutes'] . 'M'));

        $this->resets->create((int) $user['id'], $hash, $expiresAt);

        $link = $this->env['app']['frontend_reset_url'] . '?token=' . urlencode($token);
        $templatePath = dirname(__DIR__, 2) . '/templates/forgot_password_email.html';
        $this->email->sendForgotPassword($user['email'], $user['name'], $link, $templatePath);
    }

    public function validate(string $token): bool
    {
        $row = $this->resets->findValidByHash(hash('sha256', $token));
        return $row !== null;
    }

    public function reset(string $token, string $newPassword): bool
    {
        $row = $this->resets->findValidByHash(hash('sha256', $token));
        if (!$row) {
            return false;
        }

        $this->users->updatePassword((int) $row['user_id'], password_hash($newPassword, PASSWORD_ARGON2ID));
        $this->resets->markUsed((int) $row['id']);

        return true;
    }
}
