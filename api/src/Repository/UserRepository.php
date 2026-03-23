<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $email, string $passwordHash, string $displayName, string $timezone): array
    {
        $now = DateTimeHelper::nowUtc();

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, display_name, timezone, created_at)
             VALUES (:email, :password_hash, :display_name, :timezone, :created_at)'
        );

        $stmt->execute([
            'email' => mb_strtolower(trim($email)),
            'password_hash' => $passwordHash,
            'display_name' => trim($displayName),
            'timezone' => $timezone,
            'created_at' => $now,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, display_name, timezone, created_at
             FROM users
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function findForAuthByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM users
             WHERE email = :email'
        );
        $stmt->execute(['email' => mb_strtolower(trim($email))]);

        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, display_name, timezone, created_at
             FROM users
             WHERE email = :email'
        );
        $stmt->execute(['email' => mb_strtolower(trim($email))]);

        return $stmt->fetch() ?: null;
    }
}
