<?php

declare(strict_types=1);

namespace TaskHost\Infrastructure\Mail;

final class NullMailer implements MailerInterface
{
    public function send(array $message): MailResult
    {
        return new MailResult('null', bin2hex(random_bytes(16)));
    }
}
