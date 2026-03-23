<?php

declare(strict_types=1);

namespace TaskHost\Infrastructure\Mail;

use RuntimeException;

final class FileMailer implements MailerInterface
{
    public function __construct(private readonly string $outputDir)
    {
    }

    public function send(array $message): MailResult
    {
        if (!is_dir($this->outputDir) && !mkdir($concurrentDirectory = $this->outputDir, 0775, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException('Mail-Ausgabeverzeichnis konnte nicht erstellt werden: ' . $this->outputDir);
        }

        if (!is_writable($this->outputDir)) {
            throw new RuntimeException('Mail-Ausgabeverzeichnis ist nicht beschreibbar: ' . $this->outputDir);
        }

        $messageId = bin2hex(random_bytes(16));
        $timestamp = gmdate('Ymd_His');
        $filePath = rtrim($this->outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $timestamp . '_' . $messageId . '.eml';

        $headers = $message['headers'] ?? [];
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $payload = implode("\n", [
            'To: ' . trim(($message['to_name'] ?? '') !== '' ? ($message['to_name'] . ' <' . $message['to_email'] . '>') : $message['to_email']),
            'Subject: ' . ($message['subject'] ?? ''),
            ...$headerLines,
            '',
            '--- TEXT ---',
            (string) ($message['text_body'] ?? ''),
            '',
            '--- HTML ---',
            (string) ($message['html_body'] ?? ''),
        ]);

        if (file_put_contents($filePath, $payload) === false) {
            throw new RuntimeException('Mail-Datei konnte nicht geschrieben werden: ' . $filePath);
        }

        return new MailResult('file', $messageId);
    }
}
