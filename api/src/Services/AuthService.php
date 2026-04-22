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

    public function signup(array $data): array
    {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        return $this->users->create($data);
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
}
