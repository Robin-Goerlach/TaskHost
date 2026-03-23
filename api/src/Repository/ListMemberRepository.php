<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class ListMemberRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function add(int $listId, int $userId, string $role, int $addedByUserId): void
    {
        $existingRole = $this->memberRole($listId, $userId);

        if ($existingRole === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO list_members (list_id, user_id, role, added_by_user_id, created_at)
                 VALUES (:list_id, :user_id, :role, :added_by_user_id, :created_at)'
            );
            $stmt->execute([
                'list_id' => $listId,
                'user_id' => $userId,
                'role' => $role,
                'added_by_user_id' => $addedByUserId,
                'created_at' => DateTimeHelper::nowUtc(),
            ]);

            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE list_members
             SET role = :role, added_by_user_id = :added_by_user_id
             WHERE list_id = :list_id AND user_id = :user_id'
        );
        $stmt->execute([
            'list_id' => $listId,
            'user_id' => $userId,
            'role' => $role,
            'added_by_user_id' => $addedByUserId,
        ]);
    }

    public function membersForList(int $listId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT lm.list_id, lm.user_id, lm.role, lm.created_at, u.email, u.display_name
             FROM list_members lm
             INNER JOIN users u ON u.id = lm.user_id
             WHERE lm.list_id = :list_id
             ORDER BY lm.created_at ASC'
        );
        $stmt->execute(['list_id' => $listId]);

        return $stmt->fetchAll();
    }

    public function memberRole(int $listId, int $userId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT role
             FROM list_members
             WHERE list_id = :list_id AND user_id = :user_id'
        );
        $stmt->execute([
            'list_id' => $listId,
            'user_id' => $userId,
        ]);

        $row = $stmt->fetch();

        return $row['role'] ?? null;
    }

    public function remove(int $listId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM list_members
             WHERE list_id = :list_id AND user_id = :user_id'
        );
        $stmt->execute([
            'list_id' => $listId,
            'user_id' => $userId,
        ]);
    }

    public function isUserMember(int $listId, int $userId): bool
    {
        return $this->memberRole($listId, $userId) !== null;
    }
}
