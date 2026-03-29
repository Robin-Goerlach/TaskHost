<?php

declare(strict_types=1);

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function home(array $params = []): void
    {
        if ($this->app->auth->check()) {
            $this->redirect('/dashboard');
        }

        $this->redirect('/login');
    }

    public function index(array $params = []): void
    {
        $userId = $this->currentUserId();
        $lists = $this->app->lists->findAllByUserIdWithStats($userId);
        $user = $this->app->auth->user();

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'lists' => $lists,
        ]);
    }
}
