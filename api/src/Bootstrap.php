<?php

/**
 * TaskHost application bootstrap.
 *
 * This bootstrap is responsible for wiring the service container-like runtime
 * structure used by the project. It also contains the canonical route
 * registration and the deployment-aware base-path detection for shared hosting.
 *
 * @package TaskHost
 */

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
use TaskHost\Infrastructure\Mail\MailerFactory;
use TaskHost\Repository\AttachmentRepository;
use TaskHost\Repository\AuthTokenRepository;
use TaskHost\Repository\CommentRepository;
use TaskHost\Repository\FolderRepository;
use TaskHost\Repository\InvitationRepository;
use TaskHost\Repository\ListMemberRepository;
use TaskHost\Repository\MailMessageRepository;
use TaskHost\Repository\NoteRepository;
use TaskHost\Repository\QueueJobRepository;
use TaskHost\Repository\ReminderRepository;
use TaskHost\Repository\SubtaskRepository;
use TaskHost\Repository\TaskListRepository;
use TaskHost\Repository\TaskRepository;
use TaskHost\Repository\UserRepository;
use TaskHost\Security\AuthGuard;
use TaskHost\Security\PasswordHasher;
use TaskHost\Security\TokenService;
use TaskHost\Service\AsyncMailService;
use TaskHost\Service\AttachmentService;
use TaskHost\Service\AuthService;
use TaskHost\Service\CommentService;
use TaskHost\Service\FolderService;
use TaskHost\Service\MailTemplateService;
use TaskHost\Service\NoteService;
use TaskHost\Service\QueueWorkerService;
use TaskHost\Service\ReminderDispatchService;
use TaskHost\Service\ReminderService;
use TaskHost\Service\TaskListService;
use TaskHost\Service\TaskService;
use TaskHost\Service\ViewService;

require_once __DIR__ . '/Infrastructure/Autoloader.php';

final class Bootstrap
{
    /**
     * Creates the application runtime used by the HTTP front controller.
     */
    public static function createApplication(string $projectRoot): Application
    {
        return self::createRuntime($projectRoot)['app'];
    }

    /**
     * Creates the complete runtime state that is shared by HTTP and CLI entry points.
     *
     * @return array<string, mixed>
     */
    public static function createRuntime(string $projectRoot): array
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
        $mailMessageRepository = new MailMessageRepository($pdo);
        $queueJobRepository = new QueueJobRepository($pdo);

        $passwordHasher = new PasswordHasher();
        $tokenService = new TokenService();
        $authGuard = new AuthGuard($authTokenRepository, $tokenService);
        $mailer = MailerFactory::create($projectRoot);

        $mailTemplateService = new MailTemplateService(
            Env::get('APP_URL', 'http://127.0.0.1:8080') ?? 'http://127.0.0.1:8080',
            Env::get('FRONTEND_APP_URL'),
            Env::get('MAIL_FROM_ADDRESS', 'no-reply@taskhost.local') ?? 'no-reply@taskhost.local',
            Env::get('MAIL_FROM_NAME', 'TaskHost') ?? 'TaskHost'
        );
        $asyncMailService = new AsyncMailService($mailMessageRepository, $queueJobRepository, $mailTemplateService);
        $reminderDispatchService = new ReminderDispatchService($reminderRepository, $asyncMailService);
        $queueWorkerService = new QueueWorkerService($queueJobRepository, $mailMessageRepository, $reminderRepository, $mailer);

        $taskListService = new TaskListService(
            $taskListRepository,
            $folderRepository,
            $listMemberRepository,
            $invitationRepository,
            $userRepository,
            $asyncMailService
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

        // Canonical path for the path-based deployment model.
        self::registerVersionedRoutes(
            $router,
            '/v1',
            $authController,
            $meController,
            $folderController,
            $taskListController,
            $taskController,
            $noteController,
            $commentController,
            $reminderController,
            $attachmentController,
            $viewController
        );

        // Backward compatibility for older local setups that still use /api/v1.
        self::registerVersionedRoutes(
            $router,
            '/api/v1',
            $authController,
            $meController,
            $folderController,
            $taskListController,
            $taskController,
            $noteController,
            $commentController,
            $reminderController,
            $attachmentController,
            $viewController
        );

        $app = new Application(
            $router,
            $authGuard,
            Env::bool('APP_DEBUG', false),
            Env::get('CORS_ALLOW_ORIGIN', '*') ?? '*'
        );

        return [
            'app' => $app,
            'pdo' => $pdo,
            'repositories' => [
                'mail_messages' => $mailMessageRepository,
                'queue_jobs' => $queueJobRepository,
                'reminders' => $reminderRepository,
            ],
            'services' => [
                'async_mail' => $asyncMailService,
                'reminder_dispatch' => $reminderDispatchService,
                'queue_worker' => $queueWorkerService,
                'mail_templates' => $mailTemplateService,
            ],
        ];
    }

    /**
     * Detects the externally visible service base path.
     *
     * Examples:
     * - /taskhost/index.php => /taskhost
     * - /index.php          => ''
     */
    public static function detectBasePath(): string
    {
        $override = trim((string) (Env::get('APP_BASE_PATH', '') ?? ''));
        if ($override !== '') {
            return '/' . trim($override, '/');
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName === '') {
            return '';
        }

        $basePath = str_replace('\\', '/', dirname($scriptName));
        if ($basePath === '/' || $basePath === '.') {
            return '';
        }

        return '/' . trim($basePath, '/');
    }

    /**
     * Registers one complete versioned API surface.
     */
    private static function registerVersionedRoutes(
        Router $router,
        string $prefix,
        AuthController $authController,
        MeController $meController,
        FolderController $folderController,
        TaskListController $taskListController,
        TaskController $taskController,
        NoteController $noteController,
        CommentController $commentController,
        ReminderController $reminderController,
        AttachmentController $attachmentController,
        ViewController $viewController
    ): void {
        $prefix = '/' . trim($prefix, '/');

        $router->add('POST', $prefix . '/auth/register', [$authController, 'register']);
        $router->add('POST', $prefix . '/auth/login', [$authController, 'login']);
        $router->add('POST', $prefix . '/auth/logout', [$authController, 'logout'], true);
        $router->add('GET', $prefix . '/me', [$meController, 'show'], true);

        $router->add('GET', $prefix . '/folders', [$folderController, 'index'], true);
        $router->add('POST', $prefix . '/folders', [$folderController, 'store'], true);
        $router->add('PATCH', $prefix . '/folders/{id}', [$folderController, 'update'], true);
        $router->add('DELETE', $prefix . '/folders/{id}', [$folderController, 'destroy'], true);

        $router->add('GET', $prefix . '/lists', [$taskListController, 'index'], true);
        $router->add('POST', $prefix . '/lists', [$taskListController, 'store'], true);
        $router->add('GET', $prefix . '/lists/{id}', [$taskListController, 'show'], true);
        $router->add('PATCH', $prefix . '/lists/{id}', [$taskListController, 'update'], true);
        $router->add('DELETE', $prefix . '/lists/{id}', [$taskListController, 'destroy'], true);
        $router->add('GET', $prefix . '/lists/{id}/members', [$taskListController, 'members'], true);
        $router->add('POST', $prefix . '/lists/{id}/share', [$taskListController, 'share'], true);
        $router->add('GET', $prefix . '/lists/{id}/invitations', [$taskListController, 'invitations'], true);
        $router->add('POST', $prefix . '/lists/{id}/invitations/{invitationId}/resend', [$taskListController, 'resendInvitation'], true);
        $router->add('DELETE', $prefix . '/lists/{id}/members/{userId}', [$taskListController, 'removeMember'], true);
        $router->add('POST', $prefix . '/invitations/{token}/accept', [$taskListController, 'acceptInvitation'], true);

        $router->add('GET', $prefix . '/lists/{id}/tasks', [$taskController, 'indexForList'], true);
        $router->add('POST', $prefix . '/lists/{id}/tasks', [$taskController, 'store'], true);
        $router->add('GET', $prefix . '/tasks/{id}', [$taskController, 'show'], true);
        $router->add('PATCH', $prefix . '/tasks/{id}', [$taskController, 'update'], true);
        $router->add('DELETE', $prefix . '/tasks/{id}', [$taskController, 'destroy'], true);
        $router->add('POST', $prefix . '/tasks/{id}/complete', [$taskController, 'complete'], true);
        $router->add('POST', $prefix . '/tasks/{id}/restore', [$taskController, 'restore'], true);

        $router->add('GET', $prefix . '/tasks/{id}/subtasks', [$taskController, 'subtasks'], true);
        $router->add('POST', $prefix . '/tasks/{id}/subtasks', [$taskController, 'storeSubtask'], true);
        $router->add('PATCH', $prefix . '/subtasks/{id}', [$taskController, 'updateSubtask'], true);
        $router->add('DELETE', $prefix . '/subtasks/{id}', [$taskController, 'destroySubtask'], true);

        $router->add('GET', $prefix . '/tasks/{id}/note', [$noteController, 'show'], true);
        $router->add('PUT', $prefix . '/tasks/{id}/note', [$noteController, 'upsert'], true);

        $router->add('GET', $prefix . '/tasks/{id}/comments', [$commentController, 'indexForTask'], true);
        $router->add('POST', $prefix . '/tasks/{id}/comments', [$commentController, 'store'], true);
        $router->add('DELETE', $prefix . '/comments/{id}', [$commentController, 'destroy'], true);

        $router->add('GET', $prefix . '/tasks/{id}/reminders', [$reminderController, 'indexForTask'], true);
        $router->add('POST', $prefix . '/tasks/{id}/reminders', [$reminderController, 'store'], true);
        $router->add('PATCH', $prefix . '/reminders/{id}', [$reminderController, 'update'], true);
        $router->add('DELETE', $prefix . '/reminders/{id}', [$reminderController, 'destroy'], true);

        $router->add('GET', $prefix . '/tasks/{id}/attachments', [$attachmentController, 'indexForTask'], true);
        $router->add('POST', $prefix . '/tasks/{id}/attachments', [$attachmentController, 'store'], true);
        $router->add('GET', $prefix . '/attachments/{id}/download', [$attachmentController, 'download'], true);
        $router->add('DELETE', $prefix . '/attachments/{id}', [$attachmentController, 'destroy'], true);

        $router->add('GET', $prefix . '/views/{view}', [$viewController, 'show'], true);
        $router->add('GET', $prefix . '/search', [$taskController, 'search'], true);
    }
}
