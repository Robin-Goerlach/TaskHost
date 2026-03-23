<?php

declare(strict_types=1);

namespace TaskHost\Service;

use Throwable;
use TaskHost\Infrastructure\Config\Env;
use TaskHost\Infrastructure\Mail\MailerInterface;
use TaskHost\Repository\MailMessageRepository;
use TaskHost\Repository\QueueJobRepository;
use TaskHost\Repository\ReminderRepository;

final class QueueWorkerService
{
    public function __construct(
        private readonly QueueJobRepository $queueJobRepository,
        private readonly MailMessageRepository $mailMessageRepository,
        private readonly ReminderRepository $reminderRepository,
        private readonly MailerInterface $mailer
    ) {
    }

    public function processNext(string $queueName = 'mail'): ?array
    {
        $job = $this->queueJobRepository->claimNextAvailable($queueName);
        if ($job === null) {
            return null;
        }

        try {
            $result = match ((string) $job['job_type']) {
                'send_mail' => $this->handleSendMail($job),
                default => throw new \RuntimeException('Unbekannter Job-Typ: ' . $job['job_type']),
            };

            $this->queueJobRepository->markCompleted((int) $job['id']);

            return [
                'job_id' => (int) $job['id'],
                'job_type' => $job['job_type'],
                'status' => 'completed',
                'result' => $result,
            ];
        } catch (Throwable $e) {
            $delaySeconds = $this->calculateBackoffSeconds((int) $job['attempts']);
            $updatedJob = $this->queueJobRepository->releaseForRetry((int) $job['id'], $e->getMessage(), $delaySeconds);

            return [
                'job_id' => (int) $job['id'],
                'job_type' => $job['job_type'],
                'status' => $updatedJob['status'],
                'error' => $e->getMessage(),
                'attempts' => (int) $updatedJob['attempts'],
            ];
        }
    }

    public function drain(string $queueName = 'mail', int $limit = 50): array
    {
        $processed = [];
        for ($i = 0; $i < max(1, $limit); $i++) {
            $result = $this->processNext($queueName);
            if ($result === null) {
                break;
            }
            $processed[] = $result;
        }

        return [
            'count' => count($processed),
            'items' => $processed,
            'stats' => $this->queueJobRepository->stats(),
        ];
    }

    private function handleSendMail(array $job): array
    {
        $messageId = (int) ($job['payload']['mail_message_id'] ?? 0);
        if ($messageId <= 0) {
            throw new \RuntimeException('Queue-Job enthält keine gültige mail_message_id.');
        }

        $message = $this->mailMessageRepository->findById($messageId);
        if ($message === null) {
            throw new \RuntimeException('Mail-Nachricht nicht gefunden: ' . $messageId);
        }

        if (($message['status'] ?? null) === 'sent') {
            return [
                'mail_message_id' => $messageId,
                'provider' => $message['provider'] ?? 'unknown',
                'provider_message_id' => $message['provider_message_id'] ?? null,
                'already_sent' => true,
            ];
        }

        try {
            $mailResult = $this->mailer->send([
                'to_email' => $message['recipient_email'],
                'to_name' => $message['recipient_name'],
                'subject' => $message['subject'],
                'text_body' => $message['text_body'],
                'html_body' => $message['html_body'],
                'headers' => $message['headers'] ?? [],
            ]);
        } catch (Throwable $e) {
            $this->mailMessageRepository->markFailed($messageId, $e->getMessage());
            if (($message['related_type'] ?? null) === 'reminder' && !empty($message['related_id'])) {
                $this->reminderRepository->markDispatchAttemptFailed((int) $message['related_id'], $e->getMessage());
            }
            throw $e;
        }

        $sentMessage = $this->mailMessageRepository->markSent($messageId, $mailResult->provider, $mailResult->messageId);
        if (($message['related_type'] ?? null) === 'reminder' && !empty($message['related_id'])) {
            $this->reminderRepository->markSent((int) $message['related_id']);
        }

        return [
            'mail_message_id' => $messageId,
            'provider' => $sentMessage['provider'],
            'provider_message_id' => $sentMessage['provider_message_id'],
        ];
    }

    private function calculateBackoffSeconds(int $attempts): int
    {
        $base = max(5, Env::int('QUEUE_RETRY_BACKOFF_SECONDS', 30));
        $attempts = max(1, $attempts);

        return min(3600, $base * $attempts);
    }
}
