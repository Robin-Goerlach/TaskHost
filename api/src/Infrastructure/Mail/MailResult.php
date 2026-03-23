<?php

declare(strict_types=1);

namespace TaskHost\Infrastructure\Mail;

final class MailResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $messageId
    ) {
    }
}
