<?php

declare(strict_types=1);

namespace App\Controllers;

use DomainException;
use Throwable;

class TaskController extends BaseController
{
    public function store(array $params = []): void
    {
        $listId = (int) ($params['id'] ?? 0);

        try {
            $this->requireCsrf();
            $list = $this->app->lists->findByIdAndUserId($listId, $this->currentUserId());
            if ($list === null) {
                throw new DomainException('Die Ziel-Liste wurde nicht gefunden.');
            }

            $title = trim($_POST['title'] ?? '');
            if ($title === '') {
                throw new DomainException('Bitte gib einen Aufgabentitel ein.');
            }

            $priority = $_POST['priority'] ?? 'medium';
            if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                throw new DomainException('Ungültige Priorität.');
            }

            $dueDate = trim($_POST['due_date'] ?? '');
            $dueDate = $dueDate !== '' ? $dueDate : null;

            $this->app->tasks->create($listId, [
                'title' => $title,
                'notes' => trim($_POST['notes'] ?? ''),
                'priority' => $priority,
                'due_date' => $dueDate,
            ]);

            $this->app->flash->success('Aufgabe wurde angelegt.');
        } catch (DomainException|Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
        }

        $this->redirect('/lists/' . $listId);
    }

    public function toggle(array $params = []): void
    {
        $taskId = (int) ($params['id'] ?? 0);
        $redirect = '/dashboard';

        try {
            $this->requireCsrf();
            $task = $this->app->tasks->findByIdForUser($taskId, $this->currentUserId());

            if ($task === null) {
                throw new DomainException('Aufgabe wurde nicht gefunden.');
            }

            $this->app->tasks->toggleCompletion($taskId, (int) $task['is_completed'] === 1 ? 0 : 1);
            $this->app->flash->success('Aufgabenstatus wurde geändert.');
            $redirect = '/lists/' . $task['list_id'];
        } catch (DomainException|Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
        }

        $this->redirect($redirect);
    }

    public function update(array $params = []): void
    {
        $taskId = (int) ($params['id'] ?? 0);
        $redirect = '/dashboard';

        try {
            $this->requireCsrf();
            $task = $this->app->tasks->findByIdForUser($taskId, $this->currentUserId());

            if ($task === null) {
                throw new DomainException('Aufgabe wurde nicht gefunden.');
            }

            $title = trim($_POST['title'] ?? '');
            if ($title === '') {
                throw new DomainException('Der Aufgabentitel darf nicht leer sein.');
            }

            $priority = $_POST['priority'] ?? 'medium';
            if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                throw new DomainException('Ungültige Priorität.');
            }

            $dueDate = trim($_POST['due_date'] ?? '');
            $dueDate = $dueDate !== '' ? $dueDate : null;

            $this->app->tasks->update($taskId, [
                'title' => $title,
                'notes' => trim($_POST['notes'] ?? ''),
                'priority' => $priority,
                'due_date' => $dueDate,
            ]);

            $redirect = '/lists/' . $task['list_id'];
            $this->app->flash->success('Aufgabe wurde aktualisiert.');
        } catch (DomainException|Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
        }

        $this->redirect($redirect);
    }

    public function delete(array $params = []): void
    {
        $taskId = (int) ($params['id'] ?? 0);
        $redirect = '/dashboard';

        try {
            $this->requireCsrf();
            $task = $this->app->tasks->findByIdForUser($taskId, $this->currentUserId());
            if ($task === null) {
                throw new DomainException('Aufgabe wurde nicht gefunden.');
            }

            $this->app->tasks->delete($taskId);
            $redirect = '/lists/' . $task['list_id'];
            $this->app->flash->success('Aufgabe wurde gelöscht.');
        } catch (DomainException|Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
        }

        $this->redirect($redirect);
    }
}
