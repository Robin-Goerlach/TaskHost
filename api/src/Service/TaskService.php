<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Repository\ListMemberRepository;
use TaskHost\Repository\SubtaskRepository;
use TaskHost\Repository\TaskListRepository;
use TaskHost\Repository\TaskRepository;
use TaskHost\Support\ApiException;
use TaskHost\Support\DateTimeHelper;

final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskListRepository $taskListRepository,
        private readonly ListMemberRepository $listMemberRepository,
        private readonly SubtaskRepository $subtaskRepository,
        private readonly TaskListService $taskListService
    ) {
    }

    public function listTasks(int $listId, int $userId, bool $includeCompleted): array
    {
        $this->taskListService->show($listId, $userId);

        return $this->taskRepository->tasksForList($listId, $includeCompleted);
    }

    public function create(int $listId, int $userId, array $payload): array
    {
        $list = $this->taskListService->show($listId, $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new ApiException('Eine Aufgabe braucht einen Titel.');
        }

        $assigneeUserId = array_key_exists('assignee_user_id', $payload) && $payload['assignee_user_id'] !== null
            ? (int) $payload['assignee_user_id']
            : null;

        if ($assigneeUserId !== null
            && $assigneeUserId !== (int) $list['owner_user_id']
            && !$this->listMemberRepository->isUserMember($listId, $assigneeUserId)
        ) {
            throw new ApiException('Der gewählte Bearbeiter gehört nicht zu dieser Liste.', 422);
        }

        $recurrenceType = $payload['recurrence_type'] ?? null;
        if ($recurrenceType !== null && !in_array($recurrenceType, ['day', 'week', 'month', 'year'], true)) {
            throw new ApiException('Ungültiger Wiederholungstyp.');
        }

        return $this->taskRepository->create([
            'list_id' => $listId,
            'created_by_user_id' => $userId,
            'assignee_user_id' => $assigneeUserId,
            'title' => $title,
            'due_at' => DateTimeHelper::normalizeNullable($payload['due_at'] ?? null),
            'is_starred' => !empty($payload['is_starred']),
            'position' => (int) ($payload['position'] ?? 0),
            'recurrence_type' => $recurrenceType,
            'recurrence_interval' => max(1, (int) ($payload['recurrence_interval'] ?? 1)),
        ]);
    }

    public function show(int $taskId, int $userId): array
    {
        $task = $this->taskRepository->findById($taskId);
        if ($task === null) {
            throw new ApiException('Aufgabe nicht gefunden.', 404);
        }

        $this->taskListService->show((int) $task['list_id'], $userId);

        return $task;
    }

    public function update(int $taskId, int $userId, array $payload): array
    {
        $task = $this->show($taskId, $userId);
        $currentList = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($currentList, $userId);

        if (isset($payload['list_id'])) {
            $targetList = $this->taskListService->show((int) $payload['list_id'], $userId);
            $this->taskListService->assertCanEditList($targetList, $userId);
        }

        if (array_key_exists('assignee_user_id', $payload) && $payload['assignee_user_id'] !== null) {
            $assigneeUserId = (int) $payload['assignee_user_id'];
            $targetListId = isset($payload['list_id']) ? (int) $payload['list_id'] : (int) $task['list_id'];
            $targetList = $this->taskListRepository->findById($targetListId);

            if ($assigneeUserId !== (int) $targetList['owner_user_id']
                && !$this->listMemberRepository->isUserMember($targetListId, $assigneeUserId)
            ) {
                throw new ApiException('Der gewählte Bearbeiter gehört nicht zur Zielliste.', 422);
            }
        }

        if (isset($payload['recurrence_type']) && $payload['recurrence_type'] !== null
            && !in_array($payload['recurrence_type'], ['day', 'week', 'month', 'year'], true)
        ) {
            throw new ApiException('Ungültiger Wiederholungstyp.');
        }

        $payload = array_merge($payload, [
            'due_at' => array_key_exists('due_at', $payload) ? DateTimeHelper::normalizeNullable($payload['due_at']) : ($task['due_at'] ?? null),
        ]);

        return $this->taskRepository->update($taskId, $payload);
    }

    public function delete(int $taskId, int $userId): void
    {
        $task = $this->show($taskId, $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $this->taskRepository->delete($taskId);
    }

    public function complete(int $taskId, int $userId): array
    {
        $task = $this->show($taskId, $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $completed = $this->taskRepository->update($taskId, [
            'completed_at' => DateTimeHelper::nowUtc(),
        ]);

        if (!empty($task['recurrence_type'])) {
            $nextDue = $this->calculateNextDueAt(
                $task['due_at'] ?? DateTimeHelper::nowUtc(),
                (string) $task['recurrence_type'],
                max(1, (int) ($task['recurrence_interval'] ?? 1))
            );

            $this->taskRepository->create([
                'list_id' => (int) $task['list_id'],
                'created_by_user_id' => (int) $task['created_by_user_id'],
                'assignee_user_id' => $task['assignee_user_id'] !== null ? (int) $task['assignee_user_id'] : null,
                'title' => (string) $task['title'],
                'due_at' => $nextDue,
                'is_starred' => (bool) $task['is_starred'],
                'position' => (int) $task['position'],
                'recurrence_type' => $task['recurrence_type'],
                'recurrence_interval' => (int) $task['recurrence_interval'],
            ]);
        }

        return $completed;
    }

    public function restore(int $taskId, int $userId): array
    {
        $task = $this->show($taskId, $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        return $this->taskRepository->update($taskId, [
            'completed_at' => null,
        ]);
    }

    public function subtasks(int $taskId, int $userId): array
    {
        $this->show($taskId, $userId);

        return $this->subtaskRepository->forTask($taskId);
    }

    public function createSubtask(int $taskId, int $userId, array $payload): array
    {
        $task = $this->show($taskId, $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new ApiException('Eine Unteraufgabe braucht einen Titel.');
        }

        return $this->subtaskRepository->create(
            $taskId,
            $title,
            (int) ($payload['position'] ?? 0)
        );
    }

    public function updateSubtask(int $subtaskId, int $userId, array $payload): array
    {
        $subtask = $this->subtaskRepository->findById($subtaskId);
        if ($subtask === null) {
            throw new ApiException('Unteraufgabe nicht gefunden.', 404);
        }

        $task = $this->show((int) $subtask['task_id'], $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        return $this->subtaskRepository->update($subtaskId, $payload);
    }

    public function deleteSubtask(int $subtaskId, int $userId): void
    {
        $subtask = $this->subtaskRepository->findById($subtaskId);
        if ($subtask === null) {
            throw new ApiException('Unteraufgabe nicht gefunden.', 404);
        }

        $task = $this->show((int) $subtask['task_id'], $userId);
        $list = $this->taskListService->show((int) $task['list_id'], $userId);
        $this->taskListService->assertCanEditList($list, $userId);

        $this->subtaskRepository->delete($subtaskId);
    }

    public function search(int $userId, string $query): array
    {
        if (trim($query) === '') {
            return [];
        }

        return $this->taskRepository->searchAccessibleTasks($userId, $query);
    }

    private function calculateNextDueAt(string $baseDate, string $recurrenceType, int $interval): string
    {
        $dt = new \DateTimeImmutable($baseDate);
        $modifier = match ($recurrenceType) {
            'day' => '+' . $interval . ' day',
            'week' => '+' . $interval . ' week',
            'month' => '+' . $interval . ' month',
            'year' => '+' . $interval . ' year',
            default => '+1 day',
        };

        return $dt->modify($modifier)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
