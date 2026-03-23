<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Repository\CommentRepository;
use TaskHost\Support\ApiException;

final class CommentService
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly TaskService $taskService,
        private readonly TaskListService $taskListService
    ) {
    }

    public function listForTask(int $taskId, int $userId): array
    {
        $this->taskService->show($taskId, $userId);

        return $this->commentRepository->forTask($taskId);
    }

    public function create(int $taskId, int $userId, string $content): array
    {
        $task = $this->taskService->show($taskId, $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $content = trim($content);
        if ($content === '') {
            throw new ApiException('Ein Kommentar darf nicht leer sein.');
        }

        return $this->commentRepository->create($taskId, $userId, $content);
    }

    public function delete(int $commentId, int $userId): void
    {
        $comment = $this->commentRepository->findById($commentId);
        if ($comment === null) {
            throw new ApiException('Kommentar nicht gefunden.', 404);
        }

        $task = $this->taskService->show((int) $comment['task_id'], $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);

        $isOwner = (int) $comment['user_id'] === $userId;
        $isListEditor = (int) $list['owner_user_id'] === $userId || in_array($list['access_role'] ?? null, ['owner', 'editor'], true);

        if (!$isOwner && !$isListEditor) {
            throw new ApiException('Keine Berechtigung zum Löschen dieses Kommentars.', 403);
        }

        $this->commentRepository->delete($commentId);
    }
}
