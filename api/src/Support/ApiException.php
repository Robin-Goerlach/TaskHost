<?php

declare(strict_types=1);

namespace TaskHost\Support;

use RuntimeException;

final class ApiException extends RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 400, private readonly array $details = [])
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function details(): array
    {
        return $this->details;
    }
}
