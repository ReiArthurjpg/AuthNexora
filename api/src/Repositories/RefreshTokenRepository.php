<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use DateTimeImmutable;

final class RefreshTokenRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)');
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM refresh_tokens WHERE token_hash = :token_hash AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function deleteByHash(string $tokenHash): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM refresh_tokens WHERE token_hash = :token_hash');
        $stmt->execute(['token_hash' => $tokenHash]);
    }
    
    public function deleteAllForUser(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM refresh_tokens WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}
