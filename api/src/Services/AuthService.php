<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly JwtService $jwt
    ) {
    }

    public function signup(string $name, string $email, string $password): array
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        return $this->users->create($name, $email, $hash);
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        $token = $this->jwt->issueToken(['user_id' => (int) $user['id']]);

        return [
            'accessToken' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => 3600,
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ],
        ];
    }
}
