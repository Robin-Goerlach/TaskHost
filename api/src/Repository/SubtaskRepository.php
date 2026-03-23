<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class SubtaskRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function forTask(int $taskId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM subtasks
             WHERE task_id = :task_id
             ORDER BY position ASC, id ASC'
        );
        $stmt->execute(['task_id' => $taskId]);

        return $stmt->fetchAll();
    }

    public function create(int $taskId, string $title, int $position = 0): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO subtasks (task_id, title, is_completed, position, created_at, updated_at)
             VALUES (:task_id, :title, 0, :position, :created_at, :updated_at)'
        );
        $stmt->execute([
            'task_id' => $taskId,
            'title' => trim($title),
            'position' => $position,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subtasks WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function update(int $id, array $changes): array
    {
        $current = $this->findById($id);

        $stmt = $this->pdo->prepare(
            'UPDATE subtasks
             SET title = :title, is_completed = :is_completed, position = :position, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'title' => trim((string) ($changes['title'] ?? $current['title'])),
            'is_completed' => array_key_exists('is_completed', $changes) ? ((int) ((bool) $changes['is_completed'])) : $current['is_completed'],
            'position' => (int) ($changes['position'] ?? $current['position']),
            'updated_at' => DateTimeHelper::nowUtc(),
            'id' => $id,
        ]);

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM subtasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
