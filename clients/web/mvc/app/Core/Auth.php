<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

/**
 * Kapselt Sitzungs-Login und aktuellen Benutzer.
 */
class Auth
{
    public function __construct(private UserRepository $users)
    {
    }

    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public function user(): ?array
    {
        $userId = $this->id();

        if ($userId === null) {
            return null;
        }

        return $this->users->findById($userId);
    }

    public function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }
}
