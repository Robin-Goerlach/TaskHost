<?php

declare(strict_types=1);

namespace TaskHost;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Http\Router;
use TaskHost\Security\AuthGuard;
use TaskHost\Support\ApiException;
use Throwable;

final class Application
{
    public function __construct(
        private readonly Router $router,
        private readonly AuthGuard $authGuard,
        private readonly bool $debug,
        private readonly string $corsAllowOrigin = '*'
    ) {
    }

    public function handle(Request $request): Response
    {
        if ($request->method === 'OPTIONS') {
            return Response::noContent();
        }

        try {
            $match = $this->router->match($request);

            if ($match['authRequired'] === true) {
                $request = $request->withAttribute('user', $this->authGuard->authenticate($request));
            }

            $response = ($match['handler'])($request, ...array_values($match['params']));
            if (!$response instanceof Response) {
                throw new ApiException('Handler hat keine gültige Response geliefert.', 500);
            }

            return $response;
        } catch (ApiException $e) {
            return Response::json([
                'error' => $e->getMessage(),
                'details' => $e->details(),
            ], $e->statusCode());
        } catch (Throwable $e) {
            return Response::json([
                'error' => 'Interner Serverfehler.',
                'debug' => $this->debug ? [
                    'type' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    public function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => $this->corsAllowOrigin,
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        ];
    }
}
