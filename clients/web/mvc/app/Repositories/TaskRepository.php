<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Storage;

class TaskRepository
{
    public function __construct(private Storage $storage)
    {
    }

    public function create(int $listId, array $data): int
    {
        return $this->storage->transaction(function (array &$db) use ($listId, $data): int {
            $id = (int) $db['meta']['task_auto_id'];
            $db['meta']['task_auto_id']++;
            $now = date('c');

            $db['tasks'][] = [
                'id' => $id,
                'list_id' => $listId,
                'title' => $data['title'],
                'notes' => $data['notes'] !== '' ? $data['notes'] : null,
                'priority' => $data['priority'],
                'due_date' => $data['due_date'],
                'is_completed' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->touchListInMemory($db, $listId);
            return $id;
        });
    }

    public function findByListId(int $listId): array
    {
        return $this->storage->transaction(function (array &$db) use ($listId): array {
            $tasks = array_values(array_filter(
                $db['tasks'],
                static fn (array $task): bool => (int) $task['list_id'] === $listId
            ));

            usort($tasks, static function (array $a, array $b): int {
                if ((int) $a['is_completed'] !== (int) $b['is_completed']) {
                    return (int) $a['is_completed'] <=> (int) $b['is_completed'];
                }

                $aDue = $a['due_date'] ?? '9999-12-31';
                $bDue = $b['due_date'] ?? '9999-12-31';

                if ($aDue !== $bDue) {
                    return strcmp((string) $aDue, (string) $bDue);
                }

                return (int) $b['id'] <=> (int) $a['id'];
            });

            return $tasks;
        });
    }

    public function findByIdForUser(int $taskId, int $userId): ?array
    {
        return $this->storage->transaction(function (array &$db) use ($taskId, $userId): ?array {
            foreach ($db['tasks'] as $task) {
                if ((int) $task['id'] !== $taskId) {
                    continue;
                }

                foreach ($db['task_lists'] as $list) {
                    if ((int) $list['id'] === (int) $task['list_id'] && (int) $list['user_id'] === $userId) {
                        return $task;
                    }
                }
            }

            return null;
        });
    }

    public function toggleCompletion(int $taskId, int $isCompleted): void
    {
        $this->storage->transaction(function (array &$db) use ($taskId, $isCompleted): void {
            foreach ($db['tasks'] as &$task) {
                if ((int) $task['id'] === $taskId) {
                    $task['is_completed'] = $isCompleted;
                    $task['updated_at'] = date('c');
                    $this->touchListInMemory($db, (int) $task['list_id']);
                    break;
                }
            }
            unset($task);
        });
    }

    public function update(int $taskId, array $data): void
    {
        $this->storage->transaction(function (array &$db) use ($taskId, $data): void {
            foreach ($db['tasks'] as &$task) {
                if ((int) $task['id'] === $taskId) {
                    $task['title'] = $data['title'];
                    $task['notes'] = $data['notes'] !== '' ? $data['notes'] : null;
                    $task['priority'] = $data['priority'];
                    $task['due_date'] = $data['due_date'];
                    $task['updated_at'] = date('c');
                    $this->touchListInMemory($db, (int) $task['list_id']);
                    break;
                }
            }
            unset($task);
        });
    }

    public function delete(int $taskId): void
    {
        $this->storage->transaction(function (array &$db) use ($taskId): void {
            $listId = null;
            $db['tasks'] = array_values(array_filter(
                $db['tasks'],
                static function (array $task) use ($taskId, &$listId): bool {
                    $matches = (int) $task['id'] === $taskId;
                    if ($matches) {
                        $listId = (int) $task['list_id'];
                    }
                    return !$matches;
                }
            ));

            if ($listId !== null) {
                $this->touchListInMemory($db, $listId);
            }
        });
    }

    private function touchListInMemory(array &$db, int $listId): void
    {
        foreach ($db['task_lists'] as &$list) {
            if ((int) $list['id'] === $listId) {
                $list['updated_at'] = date('c');
                break;
            }
        }
        unset($list);
    }
}
