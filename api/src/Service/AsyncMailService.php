<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Repository\MailMessageRepository;
use TaskHost\Repository\QueueJobRepository;

final class AsyncMailService
{
    public function __construct(
        private readonly MailMessageRepository $mailMessageRepository,
        private readonly QueueJobRepository $queueJobRepository,
        private readonly MailTemplateService $mailTemplateService
    ) {
    }

    public function queueInvitation(array $invitation, array $list, array $inviter, ?string $idempotencyKey = null): array
    {
        $mail = $this->mailTemplateService->invitation($invitation, $list, $inviter);

        return $this->queueMessage(
            templateKey: 'list_invitation',
            recipientEmail: (string) $invitation['invited_email'],
            recipientName: null,
            subject: $mail['subject'],
            textBody: $mail['text_body'],
            htmlBody: $mail['html_body'],
            relatedType: 'invitation',
            relatedId: (int) $invitation['id'],
            idempotencyKey: $idempotencyKey
        );
    }

    public function queueListShared(array $list, array $recipient, array $inviter, string $role, ?string $idempotencyKey = null): array
    {
        $mail = $this->mailTemplateService->listShared($list, $recipient, $inviter, $role);

        return $this->queueMessage(
            templateKey: 'list_shared',
            recipientEmail: (string) $recipient['email'],
            recipientName: $recipient['display_name'] ?? null,
            subject: $mail['subject'],
            textBody: $mail['text_body'],
            htmlBody: $mail['html_body'],
            relatedType: 'list_share',
            relatedId: (int) $list['id'],
            idempotencyKey: $idempotencyKey
        );
    }

    public function queueReminder(array $reminderContext, ?string $idempotencyKey = null): array
    {
        $mail = $this->mailTemplateService->reminder($reminderContext);

        return $this->queueMessage(
            templateKey: 'task_reminder',
            recipientEmail: (string) $reminderContext['recipient_email'],
            recipientName: $reminderContext['recipient_name'] ?? null,
            subject: $mail['subject'],
            textBody: $mail['text_body'],
            htmlBody: $mail['html_body'],
            relatedType: 'reminder',
            relatedId: (int) $reminderContext['id'],
            idempotencyKey: $idempotencyKey
        );
    }

    public function queueMessage(
        string $templateKey,
        string $recipientEmail,
        ?string $recipientName,
        string $subject,
        string $textBody,
        ?string $htmlBody,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?string $idempotencyKey = null
    ): array {
        $message = $this->mailMessageRepository->createQueued(
            templateKey: $templateKey,
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            subject: $subject,
            textBody: $textBody,
            htmlBody: $htmlBody,
            headers: $this->mailTemplateService->defaultHeaders(),
            relatedType: $relatedType,
            relatedId: $relatedId,
            idempotencyKey: $idempotencyKey
        );

        $this->queueJobRepository->enqueue(
            jobType: 'send_mail',
            payload: ['mail_message_id' => (int) $message['id']],
            queueName: 'mail',
            maxAttempts: 5,
            dedupeKey: $idempotencyKey !== null ? 'job:' . $idempotencyKey : null
        );

        return $message;
    }
}
