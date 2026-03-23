<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class CommentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function forTask(int $taskId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.email, u.display_name
             FROM task_comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.task_id = :task_id
             ORDER BY c.created_at ASC'
        );
        $stmt->execute(['task_id' => $taskId]);

        return $stmt->fetchAll();
    }

    public function create(int $taskId, int $userId, string $content): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO task_comments (task_id, user_id, content, created_at, updated_at)
             VALUES (:task_id, :user_id, :content, :created_at, :updated_at)'
        );
        $stmt->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
            'content' => trim($content),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.email, u.display_name
             FROM task_comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM task_comments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
