<?php

declare(strict_types=1);

namespace TaskHost\Repository;

use PDO;
use RuntimeException;
use TaskHost\Support\DateTimeHelper;

final class QueueJobRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function enqueue(
        string $jobType,
        array $payload,
        string $queueName = 'default',
        ?string $availableAt = null,
        int $maxAttempts = 5,
        ?string $dedupeKey = null
    ): array {
        if ($dedupeKey !== null) {
            $existing = $this->findByDedupeKey($dedupeKey);
            if ($existing !== null && in_array($existing['status'], ['queued', 'processing', 'completed'], true)) {
                return $existing;
            }
        }

        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'INSERT INTO queue_jobs
             (queue_name, job_type, payload_json, status, attempts, max_attempts, available_at, reserved_at, finished_at, last_error, dedupe_key, created_at, updated_at)
             VALUES
             (:queue_name, :job_type, :payload_json, :status, 0, :max_attempts, :available_at, NULL, NULL, NULL, :dedupe_key, :created_at, :updated_at)'
        );
        $stmt->execute([
            'queue_name' => $queueName,
            'job_type' => $jobType,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            'status' => 'queued',
            'max_attempts' => max(1, $maxAttempts),
            'available_at' => $availableAt ?? $now,
            'dedupe_key' => $dedupeKey,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function claimNextAvailable(string $queueName = 'default'): ?array
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $now = DateTimeHelper::nowUtc();

        try {
            if ($driver === 'sqlite') {
                $this->pdo->exec('BEGIN IMMEDIATE');
                $stmt = $this->pdo->prepare(
                    'SELECT *
                     FROM queue_jobs
                     WHERE queue_name = :queue_name
                       AND status = :status
                       AND available_at <= :available_at
                     ORDER BY available_at ASC, id ASC
                     LIMIT 1'
                );
            } else {
                if (!$this->pdo->inTransaction()) {
                    $this->pdo->beginTransaction();
                }
                $stmt = $this->pdo->prepare(
                    'SELECT *
                     FROM queue_jobs
                     WHERE queue_name = :queue_name
                       AND status = :status
                       AND available_at <= :available_at
                     ORDER BY available_at ASC, id ASC
                     LIMIT 1
                     FOR UPDATE'
                );
            }

            $stmt->execute([
                'queue_name' => $queueName,
                'status' => 'queued',
                'available_at' => $now,
            ]);

            $job = $stmt->fetch();
            if ($job === false) {
                if ($driver === 'sqlite') {
                    $this->pdo->exec('COMMIT');
                } else {
                    $this->pdo->commit();
                }
                return null;
            }

            $update = $this->pdo->prepare(
                'UPDATE queue_jobs
                 SET status = :status,
                     attempts = attempts + 1,
                     reserved_at = :reserved_at,
                     updated_at = :updated_at
                 WHERE id = :id'
            );
            $update->execute([
                'status' => 'processing',
                'reserved_at' => $now,
                'updated_at' => $now,
                'id' => $job['id'],
            ]);

            if ($driver === 'sqlite') {
                $this->pdo->exec('COMMIT');
            } else {
                $this->pdo->commit();
            }

            return $this->findById((int) $job['id']);
        } catch (\Throwable $e) {
            try {
                if ($driver === 'sqlite') {
                    $this->pdo->exec('ROLLBACK');
                } elseif ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            } catch (\Throwable) {
            }

            throw $e;
        }
    }

    public function markCompleted(int $jobId): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'UPDATE queue_jobs
             SET status = :status,
                 finished_at = :finished_at,
                 last_error = NULL,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'completed',
            'finished_at' => $now,
            'updated_at' => $now,
            'id' => $jobId,
        ]);

        return $this->findById($jobId);
    }

    public function releaseForRetry(int $jobId, string $error, int $delaySeconds): array
    {
        $job = $this->findById($jobId);
        if ($job === null) {
            throw new RuntimeException('Queue-Job nicht gefunden.');
        }

        $attempts = (int) $job['attempts'];
        $maxAttempts = (int) $job['max_attempts'];
        if ($attempts >= $maxAttempts) {
            return $this->markFailed($jobId, $error);
        }

        $availableAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('+' . max(1, $delaySeconds) . ' seconds')
            ->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            'UPDATE queue_jobs
             SET status = :status,
                 available_at = :available_at,
                 reserved_at = NULL,
                 last_error = :last_error,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'queued',
            'available_at' => $availableAt,
            'last_error' => mb_substr($error, 0, 4000),
            'updated_at' => DateTimeHelper::nowUtc(),
            'id' => $jobId,
        ]);

        return $this->findById($jobId);
    }

    public function markFailed(int $jobId, string $error): array
    {
        $now = DateTimeHelper::nowUtc();
        $stmt = $this->pdo->prepare(
            'UPDATE queue_jobs
             SET status = :status,
                 finished_at = :finished_at,
                 last_error = :last_error,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'failed',
            'finished_at' => $now,
            'last_error' => mb_substr($error, 0, 4000),
            'updated_at' => $now,
            'id' => $jobId,
        ]);

        return $this->findById($jobId);
    }

    public function findById(int $jobId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM queue_jobs WHERE id = :id');
        $stmt->execute(['id' => $jobId]);
        $job = $stmt->fetch();

        if ($job === false) {
            return null;
        }

        $job['payload'] = json_decode((string) $job['payload_json'], true) ?: [];

        return $job;
    }

    public function findByDedupeKey(string $dedupeKey): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM queue_jobs WHERE dedupe_key = :dedupe_key ORDER BY id DESC LIMIT 1');
        $stmt->execute(['dedupe_key' => $dedupeKey]);
        $job = $stmt->fetch();

        if ($job === false) {
            return null;
        }

        $job['payload'] = json_decode((string) $job['payload_json'], true) ?: [];

        return $job;
    }

    public function stats(): array
    {
        $stmt = $this->pdo->query(
            'SELECT status, COUNT(*) AS total
             FROM queue_jobs
             GROUP BY status'
        );

        $stats = [
            'queued' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
        ];

        foreach ($stmt->fetchAll() as $row) {
            $stats[(string) $row['status']] = (int) $row['total'];
        }

        return $stats;
    }
}
