<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower($email)]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByGoogleId(string $googleId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE google_id = :google_id LIMIT 1');
        $stmt->execute(['google_id' => $googleId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, phone, birth_date, gender, cpf, address, belt, degree, last_graduation, academy_name, is_email_verified FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, google_id, academy_name, phone, birth_date, gender, cpf, address, belt, degree, last_graduation, created_by) VALUES (:name, :email, :password_hash, :google_id, :academy_name, :phone, :birth_date, :gender, :cpf, :address, :belt, :degree, :last_graduation, :created_by)');
        
        $stmt->execute([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password_hash' => $data['password_hash'] ?? null,
            'google_id' => $data['google_id'] ?? null,
            'academy_name' => $data['academy_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'address' => $data['address'] ?? null,
            'belt' => $data['belt'] ?? null,
            'degree' => $data['degree'] ?? null,
            'last_graduation' => $data['last_graduation'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'address' => $data['address'] ?? null,
            'belt' => $data['belt'] ?? null,
            'degree' => $data['degree'] ?? null,
            'last_graduation' => $data['last_graduation'] ?? null,
            'academy_name' => $data['academy_name'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ];
    }

    public function linkGoogleAccount(int $userId, string $googleId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET google_id = :google_id WHERE id = :id');
        $stmt->execute([
            'id' => $userId,
            'google_id' => $googleId,
        ]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute([
            'id' => $userId,
            'password_hash' => $passwordHash,
        ]);
    }

    public function update(int $userId, array $data): void
    {
        $fields = [
            'name' => $data['name'],
            'academy_name' => $data['academy_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'address' => $data['address'] ?? null,
            'belt' => $data['belt'] ?? null,
            'degree' => $data['degree'] ?? null,
            'last_graduation' => $data['last_graduation'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];

        $sql = 'UPDATE users SET name = :name, academy_name = :academy_name, phone = :phone, birth_date = :birth_date, gender = :gender, cpf = :cpf, address = :address, belt = :belt, degree = :degree, last_graduation = :last_graduation, updated_by = :updated_by WHERE id = :id';
        
        $stmt = $this->pdo->prepare($sql);
        $fields['id'] = $userId;
        $stmt->execute($fields);
    }
}
