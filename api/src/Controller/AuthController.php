<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Service\AuthService;

final class AuthController
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(Request $request): Response
    {
        $result = $this->authService->register(
            (string) $request->input('email', ''),
            (string) $request->input('password', ''),
            (string) $request->input('display_name', ''),
            (string) $request->input('timezone', 'Europe/Berlin')
        );

        return Response::json($result, 201);
    }

    public function login(Request $request): Response
    {
        $result = $this->authService->login(
            (string) $request->input('email', ''),
            (string) $request->input('password', '')
        );

        return Response::json($result);
    }

    public function logout(Request $request): Response
    {
        $token = $request->bearerToken();
        if ($token !== null) {
            $this->authService->logout($token);
        }

        return Response::noContent();
    }
}
