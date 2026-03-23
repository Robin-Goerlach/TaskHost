<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Repository\FolderRepository;
use TaskHost\Repository\InvitationRepository;
use TaskHost\Repository\ListMemberRepository;
use TaskHost\Repository\TaskListRepository;
use TaskHost\Repository\UserRepository;
use TaskHost\Support\ApiException;
use TaskHost\Support\DateTimeHelper;

final class TaskListService
{
    public function __construct(
        private readonly TaskListRepository $taskListRepository,
        private readonly FolderRepository $folderRepository,
        private readonly ListMemberRepository $listMemberRepository,
        private readonly InvitationRepository $invitationRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    public function allForUser(int $userId): array
    {
        return $this->taskListRepository->allAccessibleByUser($userId);
    }

    public function create(int $userId, array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new ApiException('Eine Liste braucht einen Titel.');
        }

        $folderId = isset($payload['folder_id']) ? (int) $payload['folder_id'] : null;
        if ($folderId !== null && $this->folderRepository->findOwnedByUser($folderId, $userId) === null) {
            throw new ApiException('Der angegebene Ordner gehört nicht zum Benutzer.', 422);
        }

        return $this->taskListRepository->create(
            $userId,
            $title,
            $folderId,
            $payload['color'] ?? null,
            false,
            (int) ($payload['position'] ?? 0)
        );
    }

    public function show(int $listId, int $userId): array
    {
        $list = $this->taskListRepository->findAccessibleByUser($listId, $userId);
        if ($list === null) {
            throw new ApiException('Liste nicht gefunden.', 404);
        }

        return $list;
    }

    public function update(int $listId, int $userId, array $payload): array
    {
        $list = $this->show($listId, $userId);
        $this->assertCanEditList($list, $userId);

        if (isset($payload['folder_id']) && $payload['folder_id'] !== null) {
            $folderId = (int) $payload['folder_id'];
            if ($this->folderRepository->findOwnedByUser($folderId, $userId) === null) {
                throw new ApiException('Der Zielordner gehört nicht zum Benutzer.', 422);
            }
        }

        return $this->taskListRepository->update($listId, $payload);
    }

    public function delete(int $listId, int $userId): void
    {
        $list = $this->show($listId, $userId);

        if ((int) $list['owner_user_id'] !== $userId) {
            throw new ApiException('Nur der Eigentümer darf eine Liste löschen.', 403);
        }

        $this->taskListRepository->delete($listId);
    }

    public function members(int $listId, int $userId): array
    {
        $this->show($listId, $userId);

        $list = $this->taskListRepository->findById($listId);
        $members = $this->listMemberRepository->membersForList($listId);

        if (!in_array((int) $list['owner_user_id'], array_map(static fn(array $m): int => (int) $m['user_id'], $members), true)) {
            $owner = $this->userRepository->findById((int) $list['owner_user_id']);
            if ($owner !== null) {
                array_unshift($members, [
                    'list_id' => $listId,
                    'user_id' => $owner['id'],
                    'role' => 'owner',
                    'created_at' => $list['created_at'],
                    'email' => $owner['email'],
                    'display_name' => $owner['display_name'],
                ]);
            }
        }

        return $members;
    }

    public function pendingInvitations(int $listId, int $userId): array
    {
        $list = $this->show($listId, $userId);

        if ((int) $list['owner_user_id'] !== $userId) {
            throw new ApiException('Nur der Eigentümer darf Einladungen einsehen.', 403);
        }

        return $this->invitationRepository->pendingForList($listId);
    }

    public function share(int $listId, int $userId, array $payload): array
    {
        $list = $this->show($listId, $userId);

        if ((int) $list['owner_user_id'] !== $userId) {
            throw new ApiException('Nur der Eigentümer darf eine Liste teilen.', 403);
        }

        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $role = (string) ($payload['role'] ?? 'editor');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('Ungültige E-Mail-Adresse.');
        }

        if (!in_array($role, ['editor', 'viewer'], true)) {
            throw new ApiException('Ungültige Rolle.');
        }

        $targetUser = $this->userRepository->findByEmail($email);

        if ($targetUser !== null) {
            $this->listMemberRepository->add($listId, (int) $targetUser['id'], $role, $userId);

            return [
                'mode' => 'direct_membership',
                'list_id' => $listId,
                'user' => $targetUser,
                'role' => $role,
            ];
        }

        $token = bin2hex(random_bytes(24));
        $expiresAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('+14 days')
            ->format('Y-m-d H:i:s');

        return [
            'mode' => 'invitation',
            'invitation' => $this->invitationRepository->create($listId, $email, $role, $userId, $token, $expiresAt),
            'accept_url_hint' => '/api/v1/invitations/' . $token . '/accept',
        ];
    }

    public function acceptInvitation(string $token, int $userId): array
    {
        $invitation = $this->invitationRepository->findByToken($token);

        if ($invitation === null) {
            throw new ApiException('Einladung nicht gefunden.', 404);
        }

        if ($invitation['status'] !== 'pending') {
            throw new ApiException('Diese Einladung ist nicht mehr gültig.', 409);
        }

        if (!empty($invitation['expires_at']) && strtotime((string) $invitation['expires_at']) < time()) {
            throw new ApiException('Diese Einladung ist abgelaufen.', 410);
        }

        $user = $this->userRepository->findById($userId);
        if (mb_strtolower((string) $user['email']) !== mb_strtolower((string) $invitation['invited_email'])) {
            throw new ApiException('Die Einladung gehört zu einer anderen E-Mail-Adresse.', 403);
        }

        $this->listMemberRepository->add(
            (int) $invitation['list_id'],
            $userId,
            (string) $invitation['role'],
            (int) $invitation['invited_by_user_id']
        );

        $this->invitationRepository->markAccepted((int) $invitation['id'], $userId);

        return $this->show((int) $invitation['list_id'], $userId);
    }

    public function removeMember(int $listId, int $targetUserId, int $userId): void
    {
        $list = $this->show($listId, $userId);

        if ((int) $list['owner_user_id'] !== $userId) {
            throw new ApiException('Nur der Eigentümer darf Mitglieder entfernen.', 403);
        }

        if ($targetUserId === $userId) {
            throw new ApiException('Der Eigentümer kann sich nicht selbst entfernen.', 422);
        }

        $this->listMemberRepository->remove($listId, $targetUserId);
    }

    public function assertCanEditList(array $list, int $userId): void
    {
        $isOwner = (int) $list['owner_user_id'] === $userId;
        $role = $list['access_role'] ?? null;

        if (!$isOwner && !in_array($role, ['owner', 'editor'], true)) {
            throw new ApiException('Für diese Liste fehlen Schreibrechte.', 403);
        }
    }
}
