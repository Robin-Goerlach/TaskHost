<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class ReminderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function forTask(int $taskId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM task_reminders
             WHERE task_id = :task_id
             ORDER BY remind_at ASC, id ASC'
        );
        $stmt->execute(['task_id' => $taskId]);

        return $stmt->fetchAll();
    }

    public function create(int $taskId, string $remindAt, string $channel = 'in_app'): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO task_reminders (task_id, remind_at, channel, is_sent, created_at, updated_at)
             VALUES (:task_id, :remind_at, :channel, 0, :created_at, :updated_at)'
        );
        $stmt->execute([
            'task_id' => $taskId,
            'remind_at' => DateTimeHelper::normalize($remindAt),
            'channel' => $channel,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM task_reminders WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function update(int $id, array $changes): array
    {
        $current = $this->findById($id);

        $stmt = $this->pdo->prepare(
            'UPDATE task_reminders
             SET remind_at = :remind_at, channel = :channel, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'remind_at' => array_key_exists('remind_at', $changes) ? DateTimeHelper::normalize((string)$changes['remind_at']) : $current['remind_at'],
            'channel' => $changes['channel'] ?? $current['channel'],
            'updated_at' => DateTimeHelper::nowUtc(),
            'id' => $id,
        ]);

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM task_reminders WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
