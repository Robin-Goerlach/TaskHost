<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Repository\ReminderRepository;
use TaskHost\Support\ApiException;

final class ReminderService
{
    public function __construct(
        private readonly ReminderRepository $reminderRepository,
        private readonly TaskService $taskService,
        private readonly TaskListService $taskListService
    ) {
    }

    public function listForTask(int $taskId, int $userId): array
    {
        $this->taskService->show($taskId, $userId);

        return $this->reminderRepository->forTask($taskId);
    }

    public function create(int $taskId, int $userId, array $payload): array
    {
        $task = $this->taskService->show($taskId, $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $remindAt = (string) ($payload['remind_at'] ?? '');
        if ($remindAt === '') {
            throw new ApiException('Erinnerung braucht einen Zeitpunkt.');
        }

        return $this->reminderRepository->create(
            $taskId,
            $remindAt,
            (string) ($payload['channel'] ?? 'in_app')
        );
    }

    public function update(int $reminderId, int $userId, array $payload): array
    {
        $reminder = $this->reminderRepository->findById($reminderId);
        if ($reminder === null) {
            throw new ApiException('Erinnerung nicht gefunden.', 404);
        }

        $task = $this->taskService->show((int) $reminder['task_id'], $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        return $this->reminderRepository->update($reminderId, $payload);
    }

    public function delete(int $reminderId, int $userId): void
    {
        $reminder = $this->reminderRepository->findById($reminderId);
        if ($reminder === null) {
            throw new ApiException('Erinnerung nicht gefunden.', 404);
        }

        $task = $this->taskService->show((int) $reminder['task_id'], $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $this->reminderRepository->delete($reminderId);
    }
}
