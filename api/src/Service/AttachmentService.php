<?php

/**
 * Handles attachment upload, download and cleanup.
 *
 * Attachments are stored below the project local storage directory by default.
 * The path can be overridden, but relative configuration values are always
 * resolved against the API project root so that HTTP and CLI execution behave
 * identically.
 *
 * @package TaskHost\Service
 */

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

    /**
     * Lists all attachments for one task.
     */
    public function listForTask(int $taskId, int $userId): array
    {
        $this->taskService->show($taskId, $userId);

        return $this->attachmentRepository->forTask($taskId);
    }

    /**
     * Stores one uploaded file.
     */
    public function upload(int $taskId, int $userId, array $file): array
    {
        $task = $this->taskService->show($taskId, $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new ApiException('Es wurde keine gültige Datei hochgeladen.', 422);
        }

        $uploadDir = $this->uploadDirectory();
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

    /**
     * Loads one attachment for download.
     */
    public function download(int $attachmentId, int $userId): array
    {
        $attachment = $this->attachmentRepository->findById($attachmentId);
        if ($attachment === null) {
            throw new ApiException('Anhang nicht gefunden.', 404);
        }

        $this->taskService->show((int) $attachment['task_id'], $userId);

        $path = rtrim($this->uploadDirectory(), '/\\') . DIRECTORY_SEPARATOR . $attachment['stored_name'];
        if (!is_file($path)) {
            throw new ApiException('Datei wurde im Dateisystem nicht gefunden.', 404);
        }

        return [
            'attachment' => $attachment,
            'content' => file_get_contents($path),
        ];
    }

    /**
     * Deletes one attachment and its stored file.
     */
    public function delete(int $attachmentId, int $userId): void
    {
        $attachment = $this->attachmentRepository->findById($attachmentId);
        if ($attachment === null) {
            throw new ApiException('Anhang nicht gefunden.', 404);
        }

        $task = $this->taskService->show((int) $attachment['task_id'], $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $path = rtrim($this->uploadDirectory(), '/\\') . DIRECTORY_SEPARATOR . $attachment['stored_name'];
        if (is_file($path)) {
            @unlink($path);
        }

        $this->attachmentRepository->delete($attachmentId);
    }

    /**
     * Resolves the attachment storage directory.
     */
    private function uploadDirectory(): string
    {
        return Env::resolvePath(Env::get('UPLOAD_DIR'), 'storage/uploads');
    }
}
