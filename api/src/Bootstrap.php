<?php

declare(strict_types=1);

namespace TaskHost;

use TaskHost\Controller\AttachmentController;
use TaskHost\Controller\AuthController;
use TaskHost\Controller\CommentController;
use TaskHost\Controller\FolderController;
use TaskHost\Controller\MeController;
use TaskHost\Controller\NoteController;
use TaskHost\Controller\ReminderController;
use TaskHost\Controller\TaskController;
use TaskHost\Controller\TaskListController;
use TaskHost\Controller\ViewController;
use TaskHost\Http\Router;
use TaskHost\Infrastructure\Autoloader;
use TaskHost\Infrastructure\Config\Env;
use TaskHost\Infrastructure\Database\ConnectionFactory;
use TaskHost\Repository\AttachmentRepository;
use TaskHost\Repository\AuthTokenRepository;
use TaskHost\Repository\CommentRepository;
use TaskHost\Repository\FolderRepository;
use TaskHost\Repository\InvitationRepository;
use TaskHost\Repository\ListMemberRepository;
use TaskHost\Repository\NoteRepository;
use TaskHost\Repository\ReminderRepository;
use TaskHost\Repository\SubtaskRepository;
use TaskHost\Repository\TaskListRepository;
use TaskHost\Repository\TaskRepository;
use TaskHost\Repository\UserRepository;
use TaskHost\Security\AuthGuard;
use TaskHost\Security\PasswordHasher;
use TaskHost\Security\TokenService;
use TaskHost\Service\AttachmentService;
use TaskHost\Service\AuthService;
use TaskHost\Service\CommentService;
use TaskHost\Service\FolderService;
use TaskHost\Service\NoteService;
use TaskHost\Service\ReminderService;
use TaskHost\Service\TaskListService;
use TaskHost\Service\TaskService;
use TaskHost\Service\ViewService;

require_once __DIR__ . '/Infrastructure/Autoloader.php';

final class Bootstrap
{
    public static function createApplication(string $projectRoot): Application
    {
        Env::load($projectRoot);
        date_default_timezone_set(Env::get('APP_TIMEZONE', 'Europe/Berlin') ?? 'Europe/Berlin');

        $pdo = ConnectionFactory::create();

        $userRepository = new UserRepository($pdo);
        $authTokenRepository = new AuthTokenRepository($pdo);
        $folderRepository = new FolderRepository($pdo);
        $taskListRepository = new TaskListRepository($pdo);
        $listMemberRepository = new ListMemberRepository($pdo);
        $invitationRepository = new InvitationRepository($pdo);
        $taskRepository = new TaskRepository($pdo);
        $subtaskRepository = new SubtaskRepository($pdo);
        $noteRepository = new NoteRepository($pdo);
        $commentRepository = new CommentRepository($pdo);
        $reminderRepository = new ReminderRepository($pdo);
        $attachmentRepository = new AttachmentRepository($pdo);

        $passwordHasher = new PasswordHasher();
        $tokenService = new TokenService();
        $authGuard = new AuthGuard($authTokenRepository, $tokenService);

        $taskListService = new TaskListService(
            $taskListRepository,
            $folderRepository,
            $listMemberRepository,
            $invitationRepository,
            $userRepository
        );

        $taskService = new TaskService(
            $taskRepository,
            $taskListRepository,
            $listMemberRepository,
            $subtaskRepository,
            $taskListService
        );

        $authService = new AuthService(
            $userRepository,
            $authTokenRepository,
            $taskListRepository,
            $passwordHasher,
            $tokenService
        );

        $folderService = new FolderService($folderRepository);
        $noteService = new NoteService($noteRepository, $taskService, $taskListService);
        $commentService = new CommentService($commentRepository, $taskService, $taskListService);
        $reminderService = new ReminderService($reminderRepository, $taskService, $taskListService);
        $attachmentService = new AttachmentService($attachmentRepository, $taskService, $taskListService);
        $viewService = new ViewService($taskRepository);

        $authController = new AuthController($authService);
        $meController = new MeController();
        $folderController = new FolderController($folderService);
        $taskListController = new TaskListController($taskListService);
        $taskController = new TaskController($taskService);
        $noteController = new NoteController($noteService);
        $commentController = new CommentController($commentService);
        $reminderController = new ReminderController($reminderService);
        $attachmentController = new AttachmentController($attachmentService);
        $viewController = new ViewController($viewService);

        $router = new Router();

        $router->add('POST', '/api/v1/auth/register', [$authController, 'register']);
        $router->add('POST', '/api/v1/auth/login', [$authController, 'login']);
        $router->add('POST', '/api/v1/auth/logout', [$authController, 'logout'], true);
        $router->add('GET', '/api/v1/me', [$meController, 'show'], true);

        $router->add('GET', '/api/v1/folders', [$folderController, 'index'], true);
        $router->add('POST', '/api/v1/folders', [$folderController, 'store'], true);
        $router->add('PATCH', '/api/v1/folders/{id}', [$folderController, 'update'], true);
        $router->add('DELETE', '/api/v1/folders/{id}', [$folderController, 'destroy'], true);

        $router->add('GET', '/api/v1/lists', [$taskListController, 'index'], true);
        $router->add('POST', '/api/v1/lists', [$taskListController, 'store'], true);
        $router->add('GET', '/api/v1/lists/{id}', [$taskListController, 'show'], true);
        $router->add('PATCH', '/api/v1/lists/{id}', [$taskListController, 'update'], true);
        $router->add('DELETE', '/api/v1/lists/{id}', [$taskListController, 'destroy'], true);
        $router->add('GET', '/api/v1/lists/{id}/members', [$taskListController, 'members'], true);
        $router->add('POST', '/api/v1/lists/{id}/share', [$taskListController, 'share'], true);
        $router->add('GET', '/api/v1/lists/{id}/invitations', [$taskListController, 'invitations'], true);
        $router->add('DELETE', '/api/v1/lists/{id}/members/{userId}', [$taskListController, 'removeMember'], true);
        $router->add('POST', '/api/v1/invitations/{token}/accept', [$taskListController, 'acceptInvitation'], true);

        $router->add('GET', '/api/v1/lists/{id}/tasks', [$taskController, 'indexForList'], true);
        $router->add('POST', '/api/v1/lists/{id}/tasks', [$taskController, 'store'], true);
        $router->add('GET', '/api/v1/tasks/{id}', [$taskController, 'show'], true);
        $router->add('PATCH', '/api/v1/tasks/{id}', [$taskController, 'update'], true);
        $router->add('DELETE', '/api/v1/tasks/{id}', [$taskController, 'destroy'], true);
        $router->add('POST', '/api/v1/tasks/{id}/complete', [$taskController, 'complete'], true);
        $router->add('POST', '/api/v1/tasks/{id}/restore', [$taskController, 'restore'], true);

        $router->add('GET', '/api/v1/tasks/{id}/subtasks', [$taskController, 'subtasks'], true);
        $router->add('POST', '/api/v1/tasks/{id}/subtasks', [$taskController, 'storeSubtask'], true);
        $router->add('PATCH', '/api/v1/subtasks/{id}', [$taskController, 'updateSubtask'], true);
        $router->add('DELETE', '/api/v1/subtasks/{id}', [$taskController, 'destroySubtask'], true);

        $router->add('GET', '/api/v1/tasks/{id}/note', [$noteController, 'show'], true);
        $router->add('PUT', '/api/v1/tasks/{id}/note', [$noteController, 'upsert'], true);

        $router->add('GET', '/api/v1/tasks/{id}/comments', [$commentController, 'indexForTask'], true);
        $router->add('POST', '/api/v1/tasks/{id}/comments', [$commentController, 'store'], true);
        $router->add('DELETE', '/api/v1/comments/{id}', [$commentController, 'destroy'], true);

        $router->add('GET', '/api/v1/tasks/{id}/reminders', [$reminderController, 'indexForTask'], true);
        $router->add('POST', '/api/v1/tasks/{id}/reminders', [$reminderController, 'store'], true);
        $router->add('PATCH', '/api/v1/reminders/{id}', [$reminderController, 'update'], true);
        $router->add('DELETE', '/api/v1/reminders/{id}', [$reminderController, 'destroy'], true);

        $router->add('GET', '/api/v1/tasks/{id}/attachments', [$attachmentController, 'indexForTask'], true);
        $router->add('POST', '/api/v1/tasks/{id}/attachments', [$attachmentController, 'store'], true);
        $router->add('GET', '/api/v1/attachments/{id}/download', [$attachmentController, 'download'], true);
        $router->add('DELETE', '/api/v1/attachments/{id}', [$attachmentController, 'destroy'], true);

        $router->add('GET', '/api/v1/views/{view}', [$viewController, 'show'], true);
        $router->add('GET', '/api/v1/search', [$taskController, 'search'], true);

        return new Application(
            $router,
            $authGuard,
            Env::bool('APP_DEBUG', false),
            Env::get('CORS_ALLOW_ORIGIN', '*') ?? '*'
        );
    }
}
