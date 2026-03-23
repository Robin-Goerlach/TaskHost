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

        return array_values(array_filter(
            $this->reminderRepository->forTask($taskId),
            static fn(array $reminder): bool => (int) $reminder['user_id'] === $userId
        ));
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

        $channel = (string) ($payload['channel'] ?? 'in_app');
        if (!in_array($channel, ['in_app', 'email', 'both'], true)) {
            throw new ApiException('Ungültiger Reminder-Kanal. Erlaubt sind in_app, email oder both.', 422);
        }

        return $this->reminderRepository->create(
            $taskId,
            $userId,
            $remindAt,
            $channel
        );
    }

    public function update(int $reminderId, int $userId, array $payload): array
    {
        $reminder = $this->reminderRepository->findById($reminderId);
        if ($reminder === null) {
            throw new ApiException('Erinnerung nicht gefunden.', 404);
        }

        if ((int) $reminder['user_id'] !== $userId) {
            throw new ApiException('Diese Erinnerung gehört zu einem anderen Benutzer.', 403);
        }

        $task = $this->taskService->show((int) $reminder['task_id'], $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        if (array_key_exists('channel', $payload) && !in_array((string) $payload['channel'], ['in_app', 'email', 'both'], true)) {
            throw new ApiException('Ungültiger Reminder-Kanal. Erlaubt sind in_app, email oder both.', 422);
        }

        return $this->reminderRepository->update($reminderId, $payload);
    }

    public function delete(int $reminderId, int $userId): void
    {
        $reminder = $this->reminderRepository->findById($reminderId);
        if ($reminder === null) {
            throw new ApiException('Erinnerung nicht gefunden.', 404);
        }

        if ((int) $reminder['user_id'] !== $userId) {
            throw new ApiException('Diese Erinnerung gehört zu einem anderen Benutzer.', 403);
        }

        $task = $this->taskService->show((int) $reminder['task_id'], $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $this->reminderRepository->delete($reminderId);
    }
}
