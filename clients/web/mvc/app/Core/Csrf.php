<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Sehr einfacher CSRF-Schutz.
 */
class Csrf
{
    public function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public function validate(?string $token): void
    {
        $sessionToken = $_SESSION['_csrf'] ?? '';

        if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            throw new RuntimeException('Ungültiges CSRF-Token. Bitte lade die Seite neu und versuche es erneut.');
        }
    }
}
