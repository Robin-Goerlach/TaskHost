<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Storage;

class TaskListRepository
{
    public function __construct(private Storage $storage)
    {
    }

    public function create(int $userId, string $title): int
    {
        return $this->storage->transaction(function (array &$db) use ($userId, $title): int {
            $id = (int) $db['meta']['task_list_auto_id'];
            $db['meta']['task_list_auto_id']++;
            $now = date('c');

            $db['task_lists'][] = [
                'id' => $id,
                'user_id' => $userId,
                'title' => $title,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            return $id;
        });
    }

    public function findAllByUserIdWithStats(int $userId): array
    {
        return $this->storage->transaction(function (array &$db) use ($userId): array {
            $lists = array_values(array_filter(
                $db['task_lists'],
                static fn (array $list): bool => (int) $list['user_id'] === $userId
            ));

            foreach ($lists as &$list) {
                $taskCount = 0;
                $completedCount = 0;

                foreach ($db['tasks'] as $task) {
                    if ((int) $task['list_id'] === (int) $list['id']) {
                        $taskCount++;
                        if ((int) $task['is_completed'] === 1) {
                            $completedCount++;
                        }
                    }
                }

                $list['task_count'] = $taskCount;
                $list['completed_count'] = $completedCount;
            }
            unset($list);

            usort($lists, static function (array $a, array $b): int {
                return strcmp((string) $b['updated_at'], (string) $a['updated_at']);
            });

            return $lists;
        });
    }

    public function findByIdAndUserId(int $listId, int $userId): ?array
    {
        return $this->storage->transaction(function (array &$db) use ($listId, $userId): ?array {
            foreach ($db['task_lists'] as $list) {
                if ((int) $list['id'] === $listId && (int) $list['user_id'] === $userId) {
                    return $list;
                }
            }

            return null;
        });
    }

    public function rename(int $listId, int $userId, string $title): bool
    {
        return $this->storage->transaction(function (array &$db) use ($listId, $userId, $title): bool {
            foreach ($db['task_lists'] as &$list) {
                if ((int) $list['id'] === $listId && (int) $list['user_id'] === $userId) {
                    $list['title'] = $title;
                    $list['updated_at'] = date('c');
                    return true;
                }
            }
            unset($list);

            return false;
        });
    }

    public function delete(int $listId, int $userId): bool
    {
        return $this->storage->transaction(function (array &$db) use ($listId, $userId): bool {
            $found = false;
            $db['task_lists'] = array_values(array_filter(
                $db['task_lists'],
                function (array $list) use ($listId, $userId, &$found): bool {
                    $matches = (int) $list['id'] === $listId && (int) $list['user_id'] === $userId;
                    if ($matches) {
                        $found = true;
                    }
                    return !$matches;
                }
            ));

            if ($found) {
                $db['tasks'] = array_values(array_filter(
                    $db['tasks'],
                    static fn (array $task): bool => (int) $task['list_id'] !== $listId
                ));
            }

            return $found;
        });
    }
}
