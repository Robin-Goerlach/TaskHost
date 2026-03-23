<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class FolderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function allForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM folders
             WHERE owner_user_id = :user_id
             ORDER BY position ASC, id ASC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function create(int $userId, string $title, int $position = 0): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO folders (owner_user_id, title, position, created_at, updated_at)
             VALUES (:owner_user_id, :title, :position, :created_at, :updated_at)'
        );
        $stmt->execute([
            'owner_user_id' => $userId,
            'title' => trim($title),
            'position' => $position,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findOwnedByUser((int) $this->pdo->lastInsertId(), $userId);
    }

    public function findOwnedByUser(int $folderId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM folders
             WHERE id = :id AND owner_user_id = :owner_user_id'
        );
        $stmt->execute([
            'id' => $folderId,
            'owner_user_id' => $userId,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function update(int $folderId, int $userId, array $changes): array
    {
        $current = $this->findOwnedByUser($folderId, $userId);
        if ($current === null) {
            throw new \RuntimeException('Folder not found');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE folders
             SET title = :title, position = :position, updated_at = :updated_at
             WHERE id = :id AND owner_user_id = :owner_user_id'
        );
        $stmt->execute([
            'title' => trim((string) ($changes['title'] ?? $current['title'])),
            'position' => (int) ($changes['position'] ?? $current['position']),
            'updated_at' => DateTimeHelper::nowUtc(),
            'id' => $folderId,
            'owner_user_id' => $userId,
        ]);

        return $this->findOwnedByUser($folderId, $userId);
    }

    public function delete(int $folderId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM folders
             WHERE id = :id AND owner_user_id = :owner_user_id'
        );
        $stmt->execute([
            'id' => $folderId,
            'owner_user_id' => $userId,
        ]);
    }
}
