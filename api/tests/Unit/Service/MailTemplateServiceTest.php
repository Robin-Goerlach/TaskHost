<?php

declare(strict_types=1);

namespace TaskHost\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use TaskHost\Service\MailTemplateService;

final class MailTemplateServiceTest extends TestCase
{
    private function createService(?string $frontendUrl = 'http://127.0.0.1:4173'): MailTemplateService
    {
        return new MailTemplateService(
            'http://127.0.0.1:8080',
            $frontendUrl,
            'no-reply@example.test',
            'TaskHost'
        );
    }

    public function testInvitationTemplateUsesFrontendAcceptUrlWhenConfigured(): void
    {
        $service = $this->createService();

        $payload = $service->invitation(
            ['token' => 'abc-token', 'role' => 'editor', 'expires_at' => '2026-03-24 08:00:00'],
            ['title' => 'Roadmap'],
            ['display_name' => 'Robin', 'email' => 'robin@example.test']
        );

        self::assertStringContainsString('Robin hat Dich zu „Roadmap“ eingeladen', $payload['subject']);
        self::assertStringContainsString('http://127.0.0.1:4173/#invite/abc-token', $payload['text_body']);
        self::assertStringContainsString('Bearbeiten', $payload['text_body']);
        self::assertStringContainsString('abc-token', $payload['html_body']);
    }

    public function testInvitationFallsBackToBackendAcceptUrlWithoutFrontendUrl(): void
    {
        $service = $this->createService('');

        $payload = $service->invitation(
            ['token' => 'server-token', 'role' => 'viewer', 'expires_at' => null],
            ['title' => 'Inbox'],
            ['email' => 'owner@example.test']
        );

        self::assertStringContainsString('http://127.0.0.1:8080/api/v1/invitations/server-token/accept', $payload['text_body']);
        self::assertStringContainsString('Lesen', $payload['text_body']);
    }

    public function testReminderTemplateContainsTaskAndDueDate(): void
    {
        $service = $this->createService();

        $payload = $service->reminder([
            'recipient_name' => 'Alex',
            'task_title' => 'Prepare release notes',
            'list_title' => 'Product',
            'remind_at' => '2026-03-25 09:00:00',
            'task_due_at' => '2026-03-25 12:00:00',
        ]);

        self::assertSame('Erinnerung: Prepare release notes', $payload['subject']);
        self::assertStringContainsString('Prepare release notes', $payload['text_body']);
        self::assertStringContainsString('Fällig am: 2026-03-25 12:00:00 UTC', $payload['text_body']);
        self::assertStringContainsString('TaskHost öffnen', $payload['html_body']);
    }

    public function testDefaultHeadersContainFromAndReplyTo(): void
    {
        $service = $this->createService();
        $headers = $service->defaultHeaders();

        self::assertSame('TaskHost <no-reply@example.test>', $headers['From']);
        self::assertSame('TaskHost <no-reply@example.test>', $headers['Reply-To']);
        self::assertSame('TaskHost API', $headers['X-Mailer']);
    }
}
