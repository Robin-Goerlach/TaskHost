<?php

declare(strict_types=1);

namespace App\Controllers;

use DomainException;
use Throwable;

class ListController extends BaseController
{
    public function store(array $params = []): void
    {
        try {
            $this->requireCsrf();

            $title = trim($_POST['title'] ?? '');
            if ($title === '') {
                throw new DomainException('Bitte gib einen Namen für die Liste ein.');
            }

            $listId = $this->app->lists->create($this->currentUserId(), $title);
            $this->app->flash->success('Liste wurde erstellt.');
            $this->redirect('/lists/' . $listId);
        } catch (DomainException|Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
            $this->redirect('/dashboard');
        }
    }

    public function show(array $params = []): void
    {
        $userId = $this->currentUserId();
        $listId = (int) ($params['id'] ?? 0);
        $list = $this->app->lists->findByIdAndUserId($listId, $userId);

        if ($list === null) {
            http_response_code(404);
            $this->render('partials/404', [
                'title' => 'Liste nicht gefunden',
            ]);
            return;
        }

        $tasks = $this->app->tasks->findByListId($listId);

        $this->render('lists/show', [
            'title' => 'Liste: ' . $list['title'],
            'list' => $list,
            'tasks' => $tasks,
        ]);
    }

    public function rename(array $params = []): void
    {
        $listId = (int) ($params['id'] ?? 0);

        try {
            $this->requireCsrf();
            $title = trim($_POST['title'] ?? '');

            if ($title === '') {
                throw new DomainException('Der Listenname darf nicht leer sein.');
            }

            $updated = $this->app->lists->rename($listId, $this->currentUserId(), $title);
            if (!$updated) {
                throw new DomainException('Die Liste konnte nicht umbenannt werden.');
            }

            $this->app->flash->success('Listenname wurde aktualisiert.');
        } catch (DomainException|Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
        }

        $this->redirect('/lists/' . $listId);
    }

    public function delete(array $params = []): void
    {
        try {
            $this->requireCsrf();
            $listId = (int) ($params['id'] ?? 0);
            $deleted = $this->app->lists->delete($listId, $this->currentUserId());

            if (!$deleted) {
                throw new DomainException('Die Liste konnte nicht gelöscht werden.');
            }

            $this->app->flash->success('Liste wurde gelöscht.');
            $this->redirect('/dashboard');
        } catch (DomainException|Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
            $this->redirect('/dashboard');
        }
    }
}
