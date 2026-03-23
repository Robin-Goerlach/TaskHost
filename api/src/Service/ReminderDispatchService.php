<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Repository\ReminderRepository;

final class ReminderDispatchService
{
    public function __construct(
        private readonly ReminderRepository $reminderRepository,
        private readonly AsyncMailService $asyncMailService
    ) {
    }

    public function enqueueDueEmailReminders(int $limit = 100): array
    {
        $reminders = $this->reminderRepository->dueEmailReminders($limit);
        $queued = [];

        foreach ($reminders as $reminder) {
            $message = $this->asyncMailService->queueReminder(
                $reminder,
                'reminder:' . (int) $reminder['id']
            );
            $this->reminderRepository->markQueued((int) $reminder['id']);
            $queued[] = [
                'reminder_id' => (int) $reminder['id'],
                'mail_message_id' => (int) $message['id'],
                'recipient_email' => $reminder['recipient_email'],
            ];
        }

        return [
            'count' => count($queued),
            'items' => $queued,
        ];
    }
}
