<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class AuthTokenRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $userId, string $tokenHash, ?string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO auth_tokens (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :token_hash, :expires_at, :created_at)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'created_at' => DateTimeHelper::nowUtc(),
        ]);
    }

    public function findUserByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.email, u.display_name, u.timezone, u.created_at, t.expires_at
             FROM auth_tokens t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = :token_hash
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);

        return $stmt->fetch() ?: null;
    }

    public function deleteByTokenHash(string $tokenHash): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM auth_tokens WHERE token_hash = :token_hash');
        $stmt->execute(['token_hash' => $tokenHash]);
    }
}
