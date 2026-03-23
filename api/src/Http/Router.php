<?php

declare(strict_types=1);

namespace TaskHost\Http;

use TaskHost\Support\ApiException;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler, bool $authRequired = false): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'authRequired' => $authRequired,
        ];
    }

    public function match(Request $request): array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $request->path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = ctype_digit($value) ? (int) $value : $value;
                }
            }

            return [
                'handler' => $route['handler'],
                'params' => $params,
                'authRequired' => $route['authRequired'],
            ];
        }

        throw new ApiException('Route nicht gefunden.', 404);
    }
}
