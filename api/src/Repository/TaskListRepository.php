<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class TaskListRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function allAccessibleByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT l.*,
                    CASE WHEN l.owner_user_id = :user_id THEN "owner" ELSE lm.role END AS access_role
             FROM task_lists l
             LEFT JOIN list_members lm
               ON lm.list_id = l.id AND lm.user_id = :user_id
             WHERE l.owner_user_id = :user_id
                OR lm.user_id = :user_id
             ORDER BY l.position ASC, l.id ASC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function create(int $ownerUserId, string $title, ?int $folderId, ?string $color, bool $isDefault = false, int $position = 0): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO task_lists (owner_user_id, folder_id, title, color, is_archived, is_default, position, created_at, updated_at)
             VALUES (:owner_user_id, :folder_id, :title, :color, 0, :is_default, :position, :created_at, :updated_at)'
        );

        $stmt->execute([
            'owner_user_id' => $ownerUserId,
            'folder_id' => $folderId,
            'title' => trim($title),
            'color' => $color,
            'is_default' => $isDefault ? 1 : 0,
            'position' => $position,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $listId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM task_lists WHERE id = :id');
        $stmt->execute(['id' => $listId]);

        return $stmt->fetch() ?: null;
    }

    public function findAccessibleByUser(int $listId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT l.*,
                    CASE WHEN l.owner_user_id = :user_id THEN "owner" ELSE lm.role END AS access_role
             FROM task_lists l
             LEFT JOIN list_members lm
               ON lm.list_id = l.id AND lm.user_id = :user_id
             WHERE l.id = :list_id
               AND (l.owner_user_id = :user_id OR lm.user_id = :user_id)
             LIMIT 1'
        );
        $stmt->execute([
            'list_id' => $listId,
            'user_id' => $userId,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function update(int $listId, array $changes): array
    {
        $current = $this->findById($listId);

        $stmt = $this->pdo->prepare(
            'UPDATE task_lists
             SET folder_id = :folder_id,
                 title = :title,
                 color = :color,
                 is_archived = :is_archived,
                 position = :position,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'folder_id' => $changes['folder_id'] ?? $current['folder_id'],
            'title' => trim((string) ($changes['title'] ?? $current['title'])),
            'color' => $changes['color'] ?? $current['color'],
            'is_archived' => isset($changes['is_archived']) ? ((int) ((bool) $changes['is_archived'])) : $current['is_archived'],
            'position' => (int) ($changes['position'] ?? $current['position']),
            'updated_at' => DateTimeHelper::nowUtc(),
            'id' => $listId,
        ]);

        return $this->findById($listId);
    }

    public function delete(int $listId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM task_lists WHERE id = :id');
        $stmt->execute(['id' => $listId]);
    }
}
