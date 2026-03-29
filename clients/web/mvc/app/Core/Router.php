<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sehr kleiner Router mit Platzhaltern wie /lists/{id}.
 */
class Router
{
    /**
     * @var array<int, array{method: string, pattern: string, handler: array{0: class-string, 1: string}, auth: bool}>
     */
    private array $routes = [];

    public function get(string $pattern, array $handler, bool $requiresAuth = false): void
    {
        $this->map('GET', $pattern, $handler, $requiresAuth);
    }

    public function post(string $pattern, array $handler, bool $requiresAuth = false): void
    {
        $this->map('POST', $pattern, $handler, $requiresAuth);
    }

    private function map(string $method, string $pattern, array $handler, bool $requiresAuth): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'auth' => $requiresAuth,
        ];
    }

    public function dispatch(string $method, string $path, App $app): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            $regex = '#^' . $regex . '$#';

            if (!preg_match($regex, $path, $matches)) {
                continue;
            }

            if ($route['auth'] && !$app->auth->check()) {
                $app->flash->error('Bitte melde dich zuerst an.');
                $this->redirect('/login');
            }

            $parameters = array_filter(
                $matches,
                static fn (string|int $key): bool => !is_int($key),
                ARRAY_FILTER_USE_KEY
            );

            [$class, $action] = $route['handler'];
            $controller = new $class($app);
            $controller->{$action}($parameters);
            return;
        }

        http_response_code(404);
        echo $app->view->render('partials/404', [
            'title' => 'Seite nicht gefunden',
        ]);
    }

    public function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}
