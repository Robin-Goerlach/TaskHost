<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Repository\TaskRepository;
use TaskHost\Support\ApiException;

final class ViewService
{
    public function __construct(private readonly TaskRepository $taskRepository)
    {
    }

    public function get(string $view, int $userId): array
    {
        if (!in_array($view, ['today', 'planned', 'starred', 'assigned', 'completed'], true)) {
            throw new ApiException('Unbekannte Ansicht.', 404);
        }

        return $this->taskRepository->viewForUser($userId, $view);
    }
}
