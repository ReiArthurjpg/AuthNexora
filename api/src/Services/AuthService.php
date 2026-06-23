<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\RefreshTokenRepository;
use DateInterval;
use DateTimeImmutable;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly JwtService $jwt,
        private readonly EmailService $email,
        private readonly array $env,
        private readonly RefreshTokenRepository $refreshTokens
    ) {
    }

    public function signup(array $data, ?int $createdBy = null): array
    {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        if ($createdBy !== null) {
            $data['created_by'] = $createdBy;
        }
        $user = $this->users->create($data);

        // Disparar e-mail de verificação
        $token = $this->jwt->issueToken([
            'user_id' => $user['id'],
            'scope' => 'email_verification'
        ]);
        
        $link = $this->env['app']['frontend_verify_email_url'] . '?token=' . urlencode($token);
        $templatePath = dirname(__DIR__, 2) . '/templates/welcome_email.html';
        $this->email->sendWelcomeEmail($user['email'], $user['name'], $link, $templatePath);

        return $user;
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return null;
        }

        if ((int) ($user['failed_login_attempts'] ?? 0) >= 3) {
            throw new RuntimeException('ACCOUNT_LOCKED');
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->users->incrementFailedLogin((int) $user['id']);
            return null;
        }

        if ((int) ($user['failed_login_attempts'] ?? 0) > 0) {
            $this->users->resetFailedLogin((int) $user['id']);
        }

        if (!empty($user['is_two_factor_enabled'])) {
            $tempToken = $this->jwt->issueToken([
                'user_id' => (int) $user['id'],
                'scope' => '2fa',
            ]);

            return [
                'requires_2fa' => true,
                'tempToken' => $tempToken,
            ];
        }

        return $this->issueTokenForUser($user);
    }

    public function issueTokenForUser(array $user): array
    {
        $token = $this->jwt->issueToken(['user_id' => (int) $user['id']]);

        $refreshToken = bin2hex(random_bytes(32));
        $hash = hash('sha256', $refreshToken);
        $ttlDays = $this->env['security']['refresh_token_ttl_days'] ?? 7;
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('P' . $ttlDays . 'D'));
        
        $this->refreshTokens->create((int) $user['id'], $hash, $expiresAt);

        return [
            'accessToken' => $token,
            'refreshToken' => $refreshToken,
            'tokenType' => 'Bearer',
            'expiresIn' => 3600,
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? null,
                'birth_date' => $user['birth_date'] ?? null,
                'gender' => $user['gender'] ?? null,
                'cpf' => $user['cpf'] ?? null,
                'address' => $user['address'] ?? null,
                'belt' => $user['belt'] ?? null,
                'degree' => $user['degree'] ?? null,
                'last_graduation' => $user['last_graduation'] ?? null,
                'academy_name' => $user['academy_name'] ?? null,
            ],
        ];
    }

    public function refreshToken(string $token): array
    {
        $hash = hash('sha256', $token);
        $row = $this->refreshTokens->findValidByHash($hash);

        if (!$row) {
            throw new RuntimeException('Refresh token inválido ou expirado');
        }

        // Revogar o token antigo (Token Rotation)
        $this->refreshTokens->deleteByHash($hash);

        $user = $this->users->findById((int) $row['user_id']);
        if (!$user) {
            throw new RuntimeException('Usuário não encontrado');
        }

        return $this->issueTokenForUser($user);
    }
    
    public function logout(string $refreshToken = null): void
    {
        if ($refreshToken) {
            $hash = hash('sha256', $refreshToken);
            $this->refreshTokens->deleteByHash($hash);
        }
    }
}
