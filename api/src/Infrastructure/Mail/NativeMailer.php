<?php

declare(strict_types=1);

namespace TaskHost\Infrastructure\Mail;

use RuntimeException;

final class NativeMailer implements MailerInterface
{
    public function send(array $message): MailResult
    {
        $to = trim(($message['to_name'] ?? '') !== '' ? ($message['to_name'] . ' <' . $message['to_email'] . '>') : $message['to_email']);
        $subject = (string) ($message['subject'] ?? '');
        $textBody = (string) ($message['text_body'] ?? '');
        $htmlBody = (string) ($message['html_body'] ?? '');
        $headers = $message['headers'] ?? [];

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        if ($htmlBody !== '') {
            $body = $this->buildMultipartBody($textBody, $htmlBody, $headerLines);
        } else {
            $headerLines[] = 'MIME-Version: 1.0';
            $headerLines[] = 'Content-Type: text/plain; charset=UTF-8';
            $headerLines[] = 'Content-Transfer-Encoding: 8bit';
            $body = $textBody;
        }

        $messageId = bin2hex(random_bytes(16));
        $headerLines[] = 'Message-ID: <' . $messageId . '@taskhost.local>';

        $sent = mail($to, $subject, $body, implode("\r\n", $headerLines));

        if ($sent !== true) {
            throw new RuntimeException('PHP mail() konnte die Nachricht nicht versenden.');
        }

        return new MailResult('native', $messageId);
    }

    private function buildMultipartBody(string $textBody, string $htmlBody, array &$headerLines): string
    {
        $boundary = 'taskhost_' . bin2hex(random_bytes(12));
        $headerLines[] = 'MIME-Version: 1.0';
        $headerLines[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        return implode("\r\n", [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $textBody,
            '',
            '--' . $boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $htmlBody,
            '',
            '--' . $boundary . '--',
            '',
        ]);
    }
}
