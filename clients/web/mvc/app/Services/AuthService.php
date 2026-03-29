<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\UserRepository;
use DomainException;

/**
 * Fachlogik für Registrierung und Anmeldung.
 */
class AuthService
{
    public function __construct(
        private UserRepository $users,
        private Auth $auth,
    ) {
    }

    public function register(string $name, string $email, string $password): int
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || $email === '' || $password === '') {
            throw new DomainException('Bitte fülle alle Felder aus.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Bitte gib eine gültige E-Mail-Adresse ein.');
        }

        if (strlen($password) < 8) {
            throw new DomainException('Das Passwort muss mindestens 8 Zeichen lang sein.');
        }

        if ($this->users->findByEmail($email) !== null) {
            throw new DomainException('Diese E-Mail-Adresse ist bereits registriert.');
        }

        $userId = $this->users->create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $this->auth->login($userId);
        return $userId;
    }

    public function login(string $email, string $password): void
    {
        $email = strtolower(trim($email));
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            throw new DomainException('Die Login-Daten sind nicht korrekt.');
        }

        $this->auth->login((int) $user['id']);
    }
}
