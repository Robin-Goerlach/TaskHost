<?php

declare(strict_types=1);

namespace TaskHost\Infrastructure\Mail;

interface MailerInterface
{
    public function send(array $message): MailResult;
}
