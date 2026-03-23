<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class TaskRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $data): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks
             (list_id, created_by_user_id, assignee_user_id, title, due_at, completed_at, is_starred, position, recurrence_type, recurrence_interval, created_at, updated_at)
             VALUES
             (:list_id, :created_by_user_id, :assignee_user_id, :title, :due_at, :completed_at, :is_starred, :position, :recurrence_type, :recurrence_interval, :created_at, :updated_at)'
        );

        $stmt->execute([
            'list_id' => $data['list_id'],
            'created_by_user_id' => $data['created_by_user_id'],
            'assignee_user_id' => $data['assignee_user_id'] ?? null,
            'title' => trim((string) $data['title']),
            'due_at' => DateTimeHelper::normalizeNullable($data['due_at'] ?? null),
            'completed_at' => $data['completed_at'] ?? null,
            'is_starred' => !empty($data['is_starred']) ? 1 : 0,
            'position' => (int) ($data['position'] ?? 0),
            'recurrence_type' => $data['recurrence_type'] ?? null,
            'recurrence_interval' => (int) ($data['recurrence_interval'] ?? 1),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function tasksForList(int $listId, bool $includeCompleted = false): array
    {
        $sql = 'SELECT *
                FROM tasks
                WHERE list_id = :list_id';

        if (!$includeCompleted) {
            $sql .= ' AND completed_at IS NULL';
        }

        $sql .= ' ORDER BY position ASC, id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['list_id' => $listId]);

        return $stmt->fetchAll();
    }

    public function findById(int $taskId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $taskId]);

        return $stmt->fetch() ?: null;
    }

    public function update(int $taskId, array $changes): array
    {
        $current = $this->findById($taskId);

        $stmt = $this->pdo->prepare(
            'UPDATE tasks
             SET list_id = :list_id,
                 assignee_user_id = :assignee_user_id,
                 title = :title,
                 due_at = :due_at,
                 completed_at = :completed_at,
                 is_starred = :is_starred,
                 position = :position,
                 recurrence_type = :recurrence_type,
                 recurrence_interval = :recurrence_interval,
                 updated_at = :updated_at
             WHERE id = :id'
        );

        $stmt->execute([
            'list_id' => $changes['list_id'] ?? $current['list_id'],
            'assignee_user_id' => array_key_exists('assignee_user_id', $changes) ? $changes['assignee_user_id'] : $current['assignee_user_id'],
            'title' => trim((string) ($changes['title'] ?? $current['title'])),
            'due_at' => array_key_exists('due_at', $changes) ? DateTimeHelper::normalizeNullable($changes['due_at']) : $current['due_at'],
            'completed_at' => array_key_exists('completed_at', $changes) ? $changes['completed_at'] : $current['completed_at'],
            'is_starred' => array_key_exists('is_starred', $changes) ? ((int) ((bool) $changes['is_starred'])) : $current['is_starred'],
            'position' => (int) ($changes['position'] ?? $current['position']),
            'recurrence_type' => array_key_exists('recurrence_type', $changes) ? $changes['recurrence_type'] : $current['recurrence_type'],
            'recurrence_interval' => (int) ($changes['recurrence_interval'] ?? $current['recurrence_interval']),
            'updated_at' => DateTimeHelper::nowUtc(),
            'id' => $taskId,
        ]);

        return $this->findById($taskId);
    }

    public function delete(int $taskId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $taskId]);
    }

    public function taskListId(int $taskId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT list_id FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $taskId]);

        $row = $stmt->fetch();

        return isset($row['list_id']) ? (int) $row['list_id'] : null;
    }

    public function searchAccessibleTasks(int $userId, string $query): array
    {
        $like = '%' . $query . '%';
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT t.*
             FROM tasks t
             INNER JOIN task_lists l ON l.id = t.list_id
             LEFT JOIN list_members lm ON lm.list_id = l.id AND lm.user_id = :user_id
             LEFT JOIN task_notes tn ON tn.task_id = t.id
             WHERE (l.owner_user_id = :user_id OR lm.user_id = :user_id)
               AND (t.title LIKE :search OR tn.content LIKE :search)
             ORDER BY t.updated_at DESC
             LIMIT 50'
        );
        $stmt->execute([
            'user_id' => $userId,
            'search' => $like,
        ]);

        return $stmt->fetchAll();
    }

    public function viewForUser(int $userId, string $view): array
    {
        $where = match ($view) {
            'today' => 't.completed_at IS NULL AND t.due_at IS NOT NULL AND substr(t.due_at, 1, 10) = :today',
            'planned' => 't.completed_at IS NULL AND t.due_at IS NOT NULL',
            'starred' => 't.completed_at IS NULL AND t.is_starred = 1',
            'assigned' => 't.completed_at IS NULL AND t.assignee_user_id = :user_id',
            'completed' => 't.completed_at IS NOT NULL',
            default => '1 = 0',
        };

        $sql = 'SELECT DISTINCT t.*
                FROM tasks t
                INNER JOIN task_lists l ON l.id = t.list_id
                LEFT JOIN list_members lm ON lm.list_id = l.id AND lm.user_id = :user_id
                WHERE (l.owner_user_id = :user_id OR lm.user_id = :user_id)
                  AND ' . $where . '
                ORDER BY COALESCE(t.due_at, t.updated_at) ASC, t.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $params = ['user_id' => $userId];

        if ($view === 'today') {
            $params['today'] = gmdate('Y-m-d');
        }

        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
