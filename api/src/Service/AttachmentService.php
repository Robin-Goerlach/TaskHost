<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Infrastructure\Config\Env;
use TaskHost\Repository\AttachmentRepository;
use TaskHost\Support\ApiException;

final class AttachmentService
{
    public function __construct(
        private readonly AttachmentRepository $attachmentRepository,
        private readonly TaskService $taskService,
        private readonly TaskListService $taskListService
    ) {
    }

    public function listForTask(int $taskId, int $userId): array
    {
        $this->taskService->show($taskId, $userId);

        return $this->attachmentRepository->forTask($taskId);
    }

    public function upload(int $taskId, int $userId, array $file): array
    {
        $task = $this->taskService->show($taskId, $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new ApiException('Es wurde keine gültige Datei hochgeladen.', 422);
        }

        $uploadDir = Env::get('UPLOAD_DIR', __DIR__ . '/../../storage/uploads');
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new ApiException('Upload-Verzeichnis konnte nicht erstellt werden.', 500);
        }

        $storedName = bin2hex(random_bytes(16)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) $file['name']);
        $target = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new ApiException('Datei konnte nicht gespeichert werden.', 500);
        }

        return $this->attachmentRepository->create(
            $taskId,
            $userId,
            (string) $file['name'],
            $storedName,
            (string) ($file['type'] ?? 'application/octet-stream'),
            (int) ($file['size'] ?? 0)
        );
    }

    public function download(int $attachmentId, int $userId): array
    {
        $attachment = $this->attachmentRepository->findById($attachmentId);
        if ($attachment === null) {
            throw new ApiException('Anhang nicht gefunden.', 404);
        }

        $this->taskService->show((int) $attachment['task_id'], $userId);

        $uploadDir = Env::get('UPLOAD_DIR', __DIR__ . '/../../storage/uploads');
        $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $attachment['stored_name'];

        if (!is_file($path)) {
            throw new ApiException('Datei wurde im Dateisystem nicht gefunden.', 404);
        }

        return [
            'attachment' => $attachment,
            'content' => file_get_contents($path),
        ];
    }

    public function delete(int $attachmentId, int $userId): void
    {
        $attachment = $this->attachmentRepository->findById($attachmentId);
        if ($attachment === null) {
            throw new ApiException('Anhang nicht gefunden.', 404);
        }

        $task = $this->taskService->show((int) $attachment['task_id'], $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $uploadDir = Env::get('UPLOAD_DIR', __DIR__ . '/../../storage/uploads');
        $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $attachment['stored_name'];

        if (is_file($path)) {
            @unlink($path);
        }

        $this->attachmentRepository->delete($attachmentId);
    }
}
