<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use TaskHost\Support\DateTimeHelper;

final class MailMessageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createQueued(
        string $templateKey,
        string $recipientEmail,
        ?string $recipientName,
        string $subject,
        string $textBody,
        ?string $htmlBody,
        array $headers = [],
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?string $idempotencyKey = null
    ): array {
        if ($idempotencyKey !== null) {
            $existing = $this->findByIdempotencyKey($idempotencyKey);
            if ($existing !== null) {
                return $existing;
            }
        }

        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO mail_messages
             (template_key, related_type, related_id, recipient_email, recipient_name, subject, text_body, html_body, headers_json, status, provider, provider_message_id, idempotency_key, error_message, queued_at, sent_at, created_at, updated_at)
             VALUES
             (:template_key, :related_type, :related_id, :recipient_email, :recipient_name, :subject, :text_body, :html_body, :headers_json, :status, NULL, NULL, :idempotency_key, NULL, :queued_at, NULL, :created_at, :updated_at)'
        );
        $stmt->execute([
            'template_key' => $templateKey,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'recipient_email' => mb_strtolower(trim($recipientEmail)),
            'recipient_name' => $recipientName !== null ? trim($recipientName) : null,
            'subject' => trim($subject),
            'text_body' => $textBody,
            'html_body' => $htmlBody,
            'headers_json' => json_encode($headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            'status' => 'queued',
            'idempotency_key' => $idempotencyKey,
            'queued_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mail_messages WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $row['headers'] = $this->decodeHeaders($row['headers_json'] ?? '{}');

        return $row;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mail_messages WHERE idempotency_key = :idempotency_key LIMIT 1');
        $stmt->execute(['idempotency_key' => $idempotencyKey]);

        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $row['headers'] = $this->decodeHeaders($row['headers_json'] ?? '{}');

        return $row;
    }

    public function markSent(int $id, string $provider, string $providerMessageId): array
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mail_messages
             SET status = :status,
                 provider = :provider,
                 provider_message_id = :provider_message_id,
                 error_message = NULL,
                 sent_at = :sent_at,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $now = DateTimeHelper::nowUtc();
        $stmt->execute([
            'status' => 'sent',
            'provider' => $provider,
            'provider_message_id' => $providerMessageId,
            'sent_at' => $now,
            'updated_at' => $now,
            'id' => $id,
        ]);

        return $this->findById($id);
    }

    public function markFailed(int $id, string $error): array
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mail_messages
             SET status = :status,
                 error_message = :error_message,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'failed',
            'error_message' => mb_substr($error, 0, 4000),
            'updated_at' => DateTimeHelper::nowUtc(),
            'id' => $id,
        ]);

        return $this->findById($id);
    }

    private function decodeHeaders(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
