<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\TaskListRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use PDO;

/**
 * Einfacher Service-Container für dieses Projekt.
 */
class App
{
    public function __construct(
        public PDO $db,
        public Auth $auth,
        public View $view,
        public Flash $flash,
        public Csrf $csrf,
        public UserRepository $users,
        public TaskListRepository $lists,
        public TaskRepository $tasks,
        public AuthService $authService,
    ) {
    }
}
