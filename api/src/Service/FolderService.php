<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Repository\FolderRepository;
use TaskHost\Support\ApiException;

final class FolderService
{
    public function __construct(private readonly FolderRepository $folderRepository)
    {
    }

    public function allForUser(int $userId): array
    {
        return $this->folderRepository->allForUser($userId);
    }

    public function create(int $userId, array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new ApiException('Ein Ordner braucht einen Titel.');
        }

        return $this->folderRepository->create(
            $userId,
            $title,
            (int) ($payload['position'] ?? 0)
        );
    }

    public function update(int $folderId, int $userId, array $payload): array
    {
        if ($this->folderRepository->findOwnedByUser($folderId, $userId) === null) {
            throw new ApiException('Ordner nicht gefunden.', 404);
        }

        return $this->folderRepository->update($folderId, $userId, $payload);
    }

    public function delete(int $folderId, int $userId): void
    {
        if ($this->folderRepository->findOwnedByUser($folderId, $userId) === null) {
            throw new ApiException('Ordner nicht gefunden.', 404);
        }

        $this->folderRepository->delete($folderId, $userId);
    }
}
