<?php

declare(strict_types=1);

namespace TaskHost\Security;

use TaskHost\Http\Request;
use TaskHost\Repository\AuthTokenRepository;
use TaskHost\Support\ApiException;

final class AuthGuard
{
    public function __construct(
        private readonly AuthTokenRepository $tokenRepository,
        private readonly TokenService $tokenService
    ) {
    }

    public function authenticate(Request $request): array
    {
        $plainToken = $request->bearerToken();

        if ($plainToken === null || $plainToken === '') {
            throw new ApiException('Authentifizierung erforderlich.', 401);
        }

        $user = $this->tokenRepository->findUserByTokenHash($this->tokenService->hashToken($plainToken));

        if ($user === null) {
            throw new ApiException('Ungültiger Zugriffstoken.', 401);
        }

        if (!empty($user['expires_at']) && strtotime((string) $user['expires_at']) < time()) {
            throw new ApiException('Zugriffstoken ist abgelaufen.', 401);
        }

        unset($user['expires_at']);

        return $user;
    }
}
