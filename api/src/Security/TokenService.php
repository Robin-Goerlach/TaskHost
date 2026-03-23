<?php

declare(strict_types=1);

namespace TaskHost\Security;

final class TokenService
{
    public function issuePlainToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
