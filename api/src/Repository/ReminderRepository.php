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

    public function create(int $taskId, int $userId, string $remindAt, string $channel = 'in_app'): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO task_reminders
             (task_id, user_id, remind_at, channel, is_sent, queued_at, sent_at, last_attempt_at, last_error, created_at, updated_at)
             VALUES (:task_id, :user_id, :remind_at, :channel, 0, NULL, NULL, NULL, NULL, :created_at, :updated_at)'
        );
        $stmt->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
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

        $resetDelivery = array_key_exists('remind_at', $changes) || array_key_exists('channel', $changes);
        $stmt = $this->pdo->prepare(
            'UPDATE task_reminders
             SET remind_at = :remind_at,
                 channel = :channel,
                 is_sent = :is_sent,
                 queued_at = :queued_at,
                 sent_at = :sent_at,
                 last_attempt_at = :last_attempt_at,
                 last_error = :last_error,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'remind_at' => array_key_exists('remind_at', $changes) ? DateTimeHelper::normalize((string) $changes['remind_at']) : $current['remind_at'],
            'channel' => $changes['channel'] ?? $current['channel'],
            'is_sent' => $resetDelivery ? 0 : $current['is_sent'],
            'queued_at' => $resetDelivery ? null : $current['queued_at'],
            'sent_at' => $resetDelivery ? null : $current['sent_at'],
            'last_attempt_at' => $resetDelivery ? null : $current['last_attempt_at'],
            'last_error' => $resetDelivery ? null : $current['last_error'],
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

    public function dueEmailReminders(int $limit = 100): array
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = 'SELECT tr.*, u.email AS recipient_email, u.display_name AS recipient_name,
                       t.title AS task_title, t.due_at AS task_due_at, t.completed_at AS task_completed_at,
                       l.id AS list_id, l.title AS list_title
                FROM task_reminders tr
                INNER JOIN users u ON u.id = tr.user_id
                INNER JOIN tasks t ON t.id = tr.task_id
                INNER JOIN task_lists l ON l.id = t.list_id
                WHERE tr.is_sent = 0
                  AND tr.queued_at IS NULL
                  AND tr.channel IN (\'email\', \'both\')
                  AND tr.remind_at <= :now
                  AND t.completed_at IS NULL
                ORDER BY tr.remind_at ASC, tr.id ASC';

        if ($driver !== 'sqlite') {
            $sql .= ' LIMIT :limit';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':now', DateTimeHelper::nowUtc());
        if ($driver !== 'sqlite') {
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return $driver === 'sqlite' ? array_slice($rows, 0, max(1, $limit)) : $rows;
    }

    public function markQueued(int $reminderId): void
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'UPDATE task_reminders
             SET queued_at = :queued_at,
                 last_attempt_at = :last_attempt_at,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'queued_at' => $now,
            'last_attempt_at' => $now,
            'updated_at' => $now,
            'id' => $reminderId,
        ]);
    }

    public function markSent(int $reminderId): void
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'UPDATE task_reminders
             SET is_sent = 1,
                 sent_at = :sent_at,
                 last_attempt_at = :last_attempt_at,
                 last_error = NULL,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'sent_at' => $now,
            'last_attempt_at' => $now,
            'updated_at' => $now,
            'id' => $reminderId,
        ]);
    }

    public function markDispatchAttemptFailed(int $reminderId, string $error): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE task_reminders
             SET last_attempt_at = :last_attempt_at,
                 last_error = :last_error,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $now = DateTimeHelper::nowUtc();
        $stmt->execute([
            'last_attempt_at' => $now,
            'last_error' => mb_substr($error, 0, 4000),
            'updated_at' => $now,
            'id' => $reminderId,
        ]);
    }
}
