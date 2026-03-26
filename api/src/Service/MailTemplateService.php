<?php

declare(strict_types=1);

namespace TaskHost\Service;

final class MailTemplateService
{
    public function __construct(
        private readonly string $appUrl,
        private readonly ?string $frontendAppUrl,
        private readonly string $fromAddress,
        private readonly string $fromName
    ) {
    }

    public function invitation(array $invitation, array $list, array $inviter): array
    {
        $acceptUrl = $this->frontendAppUrl !== null && trim($this->frontendAppUrl) !== ''
            ? rtrim($this->frontendAppUrl, '/') . '/#invite/' . $invitation['token']
            : rtrim($this->appUrl, '/') . '/v1/invitations/' . $invitation['token'] . '/accept';

        $inviterName = $inviter['display_name'] ?? $inviter['email'] ?? 'Jemand';
        $listTitle = $list['title'] ?? 'Liste';
        $roleLabel = ((string) ($invitation['role'] ?? 'viewer')) === 'editor' ? 'Bearbeiten' : 'Lesen';
        $expiresText = !empty($invitation['expires_at']) ? (string) $invitation['expires_at'] . ' UTC' : 'ohne Ablaufdatum';

        $subject = sprintf('%s hat Dich zu „%s“ eingeladen', $inviterName, $listTitle);

        $text = implode("\n", [
            'Hallo,',
            '',
            sprintf('%s hat Dich zur Liste „%s“ eingeladen.', $inviterName, $listTitle),
            'Rolle: ' . $roleLabel,
            'Einladung gültig bis: ' . $expiresText,
            '',
            'Einladung annehmen:',
            $acceptUrl,
            '',
            'Falls Du TaskHost noch nicht nutzt, kannst Du Dich mit dieser E-Mail-Adresse registrieren und danach die Einladung annehmen.',
            '',
            sprintf('Absender: %s <%s>', $this->fromName, $this->fromAddress),
        ]);

        $html = '<p>Hallo,</p>'
            . '<p><strong>' . htmlspecialchars((string) $inviterName, ENT_QUOTES, 'UTF-8') . '</strong> hat Dich zur Liste '
            . '<strong>„' . htmlspecialchars((string) $listTitle, ENT_QUOTES, 'UTF-8') . '“</strong> eingeladen.</p>'
            . '<p>Rolle: ' . htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') . '<br>Einladung gültig bis: '
            . htmlspecialchars($expiresText, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><a href="' . htmlspecialchars($acceptUrl, ENT_QUOTES, 'UTF-8') . '">Einladung annehmen</a></p>'
            . '<p>Falls Du TaskHost noch nicht nutzt, kannst Du Dich mit dieser E-Mail-Adresse registrieren und danach die Einladung annehmen.</p>';

        return [
            'subject' => $subject,
            'text_body' => $text,
            'html_body' => $html,
        ];
    }

    public function listShared(array $list, array $recipient, array $inviter, string $role): array
    {
        $appUrl = $this->frontendAppUrl !== null && trim($this->frontendAppUrl) !== ''
            ? $this->frontendAppUrl
            : $this->appUrl;

        $inviterName = $inviter['display_name'] ?? $inviter['email'] ?? 'Jemand';
        $recipientName = $recipient['display_name'] ?? $recipient['email'] ?? 'Hallo';
        $listTitle = $list['title'] ?? 'Liste';
        $roleLabel = $role === 'editor' ? 'Bearbeiten' : 'Lesen';

        $subject = sprintf('„%s“ wurde mit Dir geteilt', $listTitle);
        $text = implode("\n", [
            'Hallo ' . $recipientName . ',',
            '',
            sprintf('%s hat die Liste „%s“ mit Dir geteilt.', $inviterName, $listTitle),
            'Deine Rolle: ' . $roleLabel,
            '',
            'Anwendung öffnen:',
            $appUrl,
        ]);
        $html = '<p>Hallo ' . htmlspecialchars((string) $recipientName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>' . htmlspecialchars((string) $inviterName, ENT_QUOTES, 'UTF-8') . ' hat die Liste '
            . '<strong>„' . htmlspecialchars((string) $listTitle, ENT_QUOTES, 'UTF-8') . '“</strong> mit Dir geteilt.</p>'
            . '<p>Deine Rolle: ' . htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><a href="' . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . '">TaskHost öffnen</a></p>';

        return [
            'subject' => $subject,
            'text_body' => $text,
            'html_body' => $html,
        ];
    }

    public function reminder(array $reminderContext): array
    {
        $appUrl = $this->frontendAppUrl !== null && trim($this->frontendAppUrl) !== ''
            ? $this->frontendAppUrl
            : $this->appUrl;

        $recipientName = $reminderContext['recipient_name'] ?? $reminderContext['recipient_email'] ?? 'Hallo';
        $taskTitle = $reminderContext['task_title'] ?? 'Aufgabe';
        $listTitle = $reminderContext['list_title'] ?? 'Liste';
        $remindAt = $reminderContext['remind_at'] ?? '';
        $dueAt = $reminderContext['task_due_at'] ?? null;

        $subject = sprintf('Erinnerung: %s', $taskTitle);
        $textLines = [
            'Hallo ' . $recipientName . ',',
            '',
            sprintf('Dies ist Deine Erinnerung für die Aufgabe „%s“ in der Liste „%s“.', $taskTitle, $listTitle),
            'Erinnerung: ' . $remindAt . ' UTC',
        ];

        if (!empty($dueAt)) {
            $textLines[] = 'Fällig am: ' . $dueAt . ' UTC';
        }

        $textLines[] = '';
        $textLines[] = 'TaskHost öffnen:';
        $textLines[] = $appUrl;

        $html = '<p>Hallo ' . htmlspecialchars((string) $recipientName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Dies ist Deine Erinnerung für die Aufgabe <strong>„' . htmlspecialchars((string) $taskTitle, ENT_QUOTES, 'UTF-8') . '“</strong> '
            . 'in der Liste <strong>„' . htmlspecialchars((string) $listTitle, ENT_QUOTES, 'UTF-8') . '“</strong>.</p>'
            . '<p>Erinnerung: ' . htmlspecialchars((string) $remindAt, ENT_QUOTES, 'UTF-8') . ' UTC'
            . (!empty($dueAt) ? '<br>Fällig am: ' . htmlspecialchars((string) $dueAt, ENT_QUOTES, 'UTF-8') . ' UTC' : '')
            . '</p>'
            . '<p><a href="' . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . '">TaskHost öffnen</a></p>';

        return [
            'subject' => $subject,
            'text_body' => implode("\n", $textLines),
            'html_body' => $html,
        ];
    }

    public function defaultHeaders(): array
    {
        $from = trim($this->fromName !== '' ? ($this->fromName . ' <' . $this->fromAddress . '>') : $this->fromAddress);

        return [
            'From' => $from,
            'Reply-To' => $from,
            'X-Mailer' => 'TaskHost API',
        ];
    }
}
