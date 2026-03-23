<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class InvitationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $listId, string $email, string $role, int $invitedByUserId, string $token, ?string $expiresAt): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO list_invitations
             (list_id, invited_email, role, token, status, invited_by_user_id, accepted_by_user_id, expires_at, last_notified_at, notification_count, created_at, updated_at)
             VALUES
             (:list_id, :invited_email, :role, :token, :status, :invited_by_user_id, NULL, :expires_at, NULL, 0, :created_at, :updated_at)'
        );

        $stmt->execute([
            'list_id' => $listId,
            'invited_email' => mb_strtolower(trim($email)),
            'role' => $role,
            'token' => $token,
            'status' => 'pending',
            'invited_by_user_id' => $invitedByUserId,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function pendingForList(int $listId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM list_invitations
             WHERE list_id = :list_id AND status = :status
             ORDER BY created_at DESC'
        );
        $stmt->execute([
            'list_id' => $listId,
            'status' => 'pending',
        ]);

        return $stmt->fetchAll();
    }

    public function findById(int $invitationId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM list_invitations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $invitationId]);

        return $stmt->fetch() ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM list_invitations WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);

        return $stmt->fetch() ?: null;
    }

    public function markAccepted(int $invitationId, int $acceptedByUserId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE list_invitations
             SET status = :status, accepted_by_user_id = :accepted_by_user_id, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'accepted',
            'accepted_by_user_id' => $acceptedByUserId,
            'updated_at' => DateTimeHelper::nowUtc(),
            'id' => $invitationId,
        ]);
    }

    public function markNotificationQueued(int $invitationId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE list_invitations
             SET last_notified_at = :last_notified_at,
                 notification_count = COALESCE(notification_count, 0) + 1,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $now = DateTimeHelper::nowUtc();
        $stmt->execute([
            'last_notified_at' => $now,
            'updated_at' => $now,
            'id' => $invitationId,
        ]);
    }
}
