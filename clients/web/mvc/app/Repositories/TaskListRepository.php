<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class TaskListRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(int $userId, string $title): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO task_lists (user_id, title, created_at, updated_at) VALUES (:user_id, :title, NOW(), NOW())'
        );

        $statement->execute([
            'user_id' => $userId,
            'title' => $title,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findAllByUserIdWithStats(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT tl.*, 
                    COUNT(t.id) AS task_count,
                    SUM(CASE WHEN t.is_completed = 1 THEN 1 ELSE 0 END) AS completed_count
             FROM task_lists tl
             LEFT JOIN tasks t ON t.list_id = tl.id
             WHERE tl.user_id = :user_id
             GROUP BY tl.id
             ORDER BY tl.updated_at DESC'
        );

        $statement->execute(['user_id' => $userId]);
        $rows = $statement->fetchAll();

        return array_map(static function (array $row): array {
            $row['task_count'] = (int) ($row['task_count'] ?? 0);
            $row['completed_count'] = (int) ($row['completed_count'] ?? 0);
            return $row;
        }, $rows ?: []);
    }

    public function findByIdAndUserId(int $listId, int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM task_lists WHERE id = :id AND user_id = :user_id LIMIT 1'
        );

        $statement->execute([
            'id' => $listId,
            'user_id' => $userId,
        ]);

        $list = $statement->fetch();
        return is_array($list) ? $list : null;
    }

    public function rename(int $listId, int $userId, string $title): bool
    {
        $statement = $this->db->prepare(
            'UPDATE task_lists SET title = :title, updated_at = NOW() WHERE id = :id AND user_id = :user_id'
        );

        $statement->execute([
            'title' => $title,
            'id' => $listId,
            'user_id' => $userId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function delete(int $listId, int $userId): bool
    {
        $statement = $this->db->prepare('DELETE FROM task_lists WHERE id = :id AND user_id = :user_id');
        $statement->execute([
            'id' => $listId,
            'user_id' => $userId,
        ]);

        return $statement->rowCount() > 0;
    }
}
