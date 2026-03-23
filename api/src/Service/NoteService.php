<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Repository\NoteRepository;

final class NoteService
{
    public function __construct(
        private readonly NoteRepository $noteRepository,
        private readonly TaskService $taskService,
        private readonly TaskListService $taskListService
    ) {
    }

    public function show(int $taskId, int $userId): ?array
    {
        $this->taskService->show($taskId, $userId);

        return $this->noteRepository->findByTaskId($taskId);
    }

    public function upsert(int $taskId, int $userId, string $content): array
    {
        $task = $this->taskService->show($taskId, $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        return $this->noteRepository->upsert($taskId, $content);
    }
}
