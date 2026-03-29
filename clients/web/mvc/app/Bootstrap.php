<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ListController;
use App\Controllers\TaskController;
use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Router;
use App\Core\View;
use App\Database\Connection;
use App\Repositories\TaskListRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

require_once __DIR__ . '/Support/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

App\Config\Env::load(dirname(__DIR__) . '/.env');

error_reporting(E_ALL);
ini_set('display_errors', App\Config\Env::bool('APP_DEBUG', true) ? '1' : '0');

session_start();

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');
define('VAR_PATH', BASE_PATH . '/var');
define('DATA_PATH', VAR_PATH . '/data');

$pdo = Connection::make();
$userRepository = new UserRepository($pdo);
$taskListRepository = new TaskListRepository($pdo);
$taskRepository = new TaskRepository($pdo);

$auth = new Auth($userRepository);
$authService = new AuthService($userRepository, $auth);
$view = new View(VIEW_PATH);
$flash = new Flash();
$csrf = new Csrf();

$app = new App(
    $pdo,
    $auth,
    $view,
    $flash,
    $csrf,
    $userRepository,
    $taskListRepository,
    $taskRepository,
    $authService,
);

$router = new Router();
$router->get('/', [DashboardController::class, 'home']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout'], true);
$router->get('/dashboard', [DashboardController::class, 'index'], true);
$router->post('/lists', [ListController::class, 'store'], true);
$router->get('/lists/{id}', [ListController::class, 'show'], true);
$router->post('/lists/{id}/rename', [ListController::class, 'rename'], true);
$router->post('/lists/{id}/delete', [ListController::class, 'delete'], true);
$router->post('/lists/{id}/tasks', [TaskController::class, 'store'], true);
$router->post('/tasks/{id}/toggle', [TaskController::class, 'toggle'], true);
$router->post('/tasks/{id}/update', [TaskController::class, 'update'], true);
$router->post('/tasks/{id}/delete', [TaskController::class, 'delete'], true);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path(), $app);
