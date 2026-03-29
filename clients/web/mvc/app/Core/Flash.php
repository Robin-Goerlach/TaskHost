<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session-basierte Flash-Messages für Hinweise nach Redirects.
 */
class Flash
{
    public function success(string $message): void
    {
        $_SESSION['_flash'][] = ['type' => 'success', 'message' => $message];
    }

    public function error(string $message): void
    {
        $_SESSION['_flash'][] = ['type' => 'error', 'message' => $message];
    }

    public function info(string $message): void
    {
        $_SESSION['_flash'][] = ['type' => 'info', 'message' => $message];
    }

    /**
     * Gibt Flash-Nachrichten zurück und leert sie anschließend.
     *
     * @return array<int, array{type: string, message: string}>
     */
    public function consume(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }
}
