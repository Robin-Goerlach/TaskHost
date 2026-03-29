<?php

declare(strict_types=1);

namespace App\Controllers;

use DomainException;
use Throwable;

class AuthController extends BaseController
{
    public function showLogin(array $params = []): void
    {
        if ($this->app->auth->check()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login', [
            'title' => 'Anmelden',
        ]);
    }

    public function login(array $params = []): void
    {
        try {
            $this->requireCsrf();
            $this->rememberOldInput(['email' => $_POST['email'] ?? '']);

            $this->app->authService->login(
                $_POST['email'] ?? '',
                $_POST['password'] ?? '',
            );

            $this->forgetOldInput();
            $this->app->flash->success('Willkommen zurück. Du bist jetzt angemeldet.');
            $this->redirect('/dashboard');
        } catch (DomainException|Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
            $this->redirect('/login');
        }
    }

    public function showRegister(array $params = []): void
    {
        if ($this->app->auth->check()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/register', [
            'title' => 'Registrieren',
        ]);
    }

    public function register(array $params = []): void
    {
        try {
            $this->requireCsrf();
            $this->rememberOldInput([
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
            ]);

            $this->app->authService->register(
                $_POST['name'] ?? '',
                $_POST['email'] ?? '',
                $_POST['password'] ?? '',
            );

            $this->forgetOldInput();
            $this->app->flash->success('Registrierung erfolgreich. Dein Konto wurde angelegt.');
            $this->redirect('/dashboard');
        } catch (DomainException|Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
            $this->redirect('/register');
        }
    }

    public function logout(array $params = []): void
    {
        try {
            $this->requireCsrf();
            $this->app->auth->logout();
            $this->app->flash->info('Du wurdest erfolgreich abgemeldet.');
        } catch (Throwable $exception) {
            $this->app->flash->error($exception->getMessage());
        }

        $this->redirect('/login');
    }
}
