<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class AttachmentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function forTask(int $taskId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM task_attachments
             WHERE task_id = :task_id
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute(['task_id' => $taskId]);

        return $stmt->fetchAll();
    }

    public function create(int $taskId, int $uploadedByUserId, string $originalName, string $storedName, string $mimeType, int $fileSize): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO task_attachments (task_id, uploaded_by_user_id, original_name, stored_name, mime_type, file_size, created_at)
             VALUES (:task_id, :uploaded_by_user_id, :original_name, :stored_name, :mime_type, :file_size, :created_at)'
        );
        $stmt->execute([
            'task_id' => $taskId,
            'uploaded_by_user_id' => $uploadedByUserId,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'created_at' => DateTimeHelper::nowUtc(),
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM task_attachments WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM task_attachments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
