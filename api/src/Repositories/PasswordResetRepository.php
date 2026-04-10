<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use PDO;

final class PasswordResetRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void
    {
        // Invalida tokens anteriores não utilizados para este usuário
        $deleteStmt = $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id AND used_at IS NULL');
        $deleteStmt->execute(['user_id' => $userId]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pr.*, u.name, u.email FROM password_resets pr
             INNER JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = :token_hash
               AND pr.used_at IS NULL
               AND pr.expires_at > NOW()
             ORDER BY pr.id DESC
             LIMIT 1'
        );

        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
