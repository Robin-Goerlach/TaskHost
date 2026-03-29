<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class TaskRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(int $listId, array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO tasks (list_id, title, notes, priority, due_date, is_completed, created_at, updated_at)
             VALUES (:list_id, :title, :notes, :priority, :due_date, 0, NOW(), NOW())'
        );

        $statement->execute([
            'list_id' => $listId,
            'title' => $data['title'],
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
        ]);

        $this->touchList($listId);
        return (int) $this->db->lastInsertId();
    }

    public function findByListId(int $listId): array
    {
        $statement = $this->db->prepare(
            'SELECT *
             FROM tasks
             WHERE list_id = :list_id
             ORDER BY is_completed ASC,
                      COALESCE(due_date, "9999-12-31") ASC,
                      id DESC'
        );

        $statement->execute(['list_id' => $listId]);
        return $statement->fetchAll() ?: [];
    }

    public function findByIdForUser(int $taskId, int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT t.*
             FROM tasks t
             INNER JOIN task_lists tl ON tl.id = t.list_id
             WHERE t.id = :task_id AND tl.user_id = :user_id
             LIMIT 1'
        );

        $statement->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
        ]);

        $task = $statement->fetch();
        return is_array($task) ? $task : null;
    }

    public function toggleCompletion(int $taskId, int $isCompleted): void
    {
        $statement = $this->db->prepare(
            'UPDATE tasks SET is_completed = :is_completed, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'is_completed' => $isCompleted,
            'id' => $taskId,
        ]);

        $listId = $this->findListIdByTaskId($taskId);
        if ($listId !== null) {
            $this->touchList($listId);
        }
    }

    public function update(int $taskId, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE tasks
             SET title = :title,
                 notes = :notes,
                 priority = :priority,
                 due_date = :due_date,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'title' => $data['title'],
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
            'id' => $taskId,
        ]);

        $listId = $this->findListIdByTaskId($taskId);
        if ($listId !== null) {
            $this->touchList($listId);
        }
    }

    public function delete(int $taskId): void
    {
        $listId = $this->findListIdByTaskId($taskId);

        $statement = $this->db->prepare('DELETE FROM tasks WHERE id = :id');
        $statement->execute(['id' => $taskId]);

        if ($listId !== null) {
            $this->touchList($listId);
        }
    }

    private function findListIdByTaskId(int $taskId): ?int
    {
        $statement = $this->db->prepare('SELECT list_id FROM tasks WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $taskId]);
        $listId = $statement->fetchColumn();

        return $listId !== false ? (int) $listId : null;
    }

    private function touchList(int $listId): void
    {
        $statement = $this->db->prepare('UPDATE task_lists SET updated_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $listId]);
    }
}
