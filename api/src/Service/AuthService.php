<?php

declare(strict_types=1);

namespace TaskHost\Service;

use TaskHost\Infrastructure\Config\Env;
use TaskHost\Repository\AuthTokenRepository;
use TaskHost\Repository\TaskListRepository;
use TaskHost\Repository\UserRepository;
use TaskHost\Security\PasswordHasher;
use TaskHost\Security\TokenService;
use TaskHost\Support\ApiException;
use TaskHost\Support\DateTimeHelper;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthTokenRepository $authTokenRepository,
        private readonly TaskListRepository $taskListRepository,
        private readonly PasswordHasher $passwordHasher,
        private readonly TokenService $tokenService
    ) {
    }

    public function register(string $email, string $password, string $displayName, string $timezone): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('Ungültige E-Mail-Adresse.');
        }

        if (mb_strlen($password) < 8) {
            throw new ApiException('Das Passwort muss mindestens 8 Zeichen lang sein.');
        }

        if ($this->userRepository->findForAuthByEmail($email) !== null) {
            throw new ApiException('Für diese E-Mail-Adresse existiert bereits ein Konto.', 409);
        }

        $user = $this->userRepository->create(
            $email,
            $this->passwordHasher->hash($password),
            $displayName !== '' ? $displayName : $email,
            $timezone !== '' ? $timezone : 'Europe/Berlin'
        );

        $this->taskListRepository->create((int) $user['id'], 'Inbox', null, '#2d6cdf', true, 0);

        return $this->issueTokenForUserId((int) $user['id']);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findForAuthByEmail($email);

        if ($user === null || !$this->passwordHasher->verify($password, (string) $user['password_hash'])) {
            throw new ApiException('E-Mail oder Passwort ist falsch.', 401);
        }

        return $this->issueTokenForUserId((int) $user['id']);
    }

    public function logout(string $plainToken): void
    {
        $this->authTokenRepository->deleteByTokenHash($this->tokenService->hashToken($plainToken));
    }

    private function issueTokenForUserId(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        $plainToken = $this->tokenService->issuePlainToken();
        $tokenHash = $this->tokenService->hashToken($plainToken);

        $ttlHours = Env::int('TOKEN_TTL_HOURS', 720);
        $expiresAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('+' . $ttlHours . ' hours')
            ->format('Y-m-d H:i:s');

        $this->authTokenRepository->create($userId, $tokenHash, $expiresAt);

        return [
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
            'user' => $user,
        ];
    }
}
