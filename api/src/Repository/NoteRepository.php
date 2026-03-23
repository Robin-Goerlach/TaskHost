<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class NoteRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByTaskId(int $taskId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM task_notes WHERE task_id = :task_id');
        $stmt->execute(['task_id' => $taskId]);

        return $stmt->fetch() ?: null;
    }

    public function upsert(int $taskId, string $content): array
    {
        $current = $this->findByTaskId($taskId);
        $now = DateTimeHelper::nowUtc();

        if ($current === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO task_notes (task_id, content, created_at, updated_at)
                 VALUES (:task_id, :content, :created_at, :updated_at)'
            );
            $stmt->execute([
                'task_id' => $taskId,
                'content' => $content,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE task_notes
                 SET content = :content, updated_at = :updated_at
                 WHERE task_id = :task_id'
            );
            $stmt->execute([
                'task_id' => $taskId,
                'content' => $content,
                'updated_at' => $now,
            ]);
        }

        return $this->findByTaskId($taskId);
    }
}
