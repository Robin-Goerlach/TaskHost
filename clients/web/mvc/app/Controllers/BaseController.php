<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use RuntimeException;

/**
 * Gemeinsame Basisfunktionen für Controller.
 */
abstract class BaseController
{
    public function __construct(protected App $app)
    {
    }

    protected function render(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        echo $this->app->view->render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    protected function requireCsrf(): void
    {
        $this->app->csrf->validate($_POST['_csrf'] ?? null);
    }

    protected function rememberOldInput(array $input): void
    {
        $_SESSION['_old'] = $input;
    }

    protected function forgetOldInput(): void
    {
        unset($_SESSION['_old']);
    }

    protected function currentUserId(): int
    {
        $userId = $this->app->auth->id();

        if ($userId === null) {
            throw new RuntimeException('Kein Benutzer angemeldet.');
        }

        return $userId;
    }
}
