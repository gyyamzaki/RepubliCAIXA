<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Services\AuthService;
use InvalidArgumentException;

final class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showRegister(): void
    {
        if ($this->activeSessionUser() !== null) {
            header('Location: /dashboard');
            exit;
        }

        $pageTitle = 'Criar conta - RepubliCAIXA';
        $errors = [];
        $name = '';
        $email = '';
        $csrfToken = Csrf::token();

        require dirname(__DIR__) . '/Views/auth/register.php';
    }

    public function register(): void
    {
        if ($this->activeSessionUser() !== null) {
            header('Location: /dashboard');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
        $errors = [];
        $csrfToken = Csrf::token();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $errors[] = 'Requisição inválida. Atualize a página e tente novamente.';
        }

        if (strlen($name) < 2 || strlen($name) > 120) {
            $errors[] = 'Informe um nome válido.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'A senha deve possuir pelo menos 8 caracteres.';
        }

        if ($password !== $passwordConfirmation) {
            $errors[] = 'As senhas não coincidem.';
        }

        if (!empty($errors)) {
            $pageTitle = 'Criar conta - RepubliCAIXA';
            require dirname(__DIR__) . '/Views/auth/register.php';
            return;
        }

        try {
            $this->authService->register($name, $email, $password);
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
            $pageTitle = 'Criar conta - RepubliCAIXA';
            require dirname(__DIR__) . '/Views/auth/register.php';
            return;
        }

        header('Location: /login?registered=1');
        exit;
    }

    public function showLogin(): void
    {
        if ($this->activeSessionUser() !== null) {
            header('Location: /dashboard');
            exit;
        }

        $pageTitle = 'Login - RepubliCAIXA';
        $errors = [];
        $email = '';
        $csrfToken = Csrf::token();

        require dirname(__DIR__) . '/Views/auth/login.php';
    }

    public function login(): void
    {
        if ($this->activeSessionUser() !== null) {
            header('Location: /dashboard');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];
        $csrfToken = Csrf::token();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $errors[] = 'Requisição inválida. Atualize a página e tente novamente.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido.';
        }

        if ($password === '') {
            $errors[] = 'Informe sua senha.';
        }

        if (!empty($errors)) {
            $pageTitle = 'Login - RepubliCAIXA';
            require dirname(__DIR__) . '/Views/auth/login.php';
            return;
        }

        $user = $this->authService->authenticate($email, $password);

        if ($user === null) {
            $errors[] = 'E-mail ou senha incorretos.';
            $pageTitle = 'Login - RepubliCAIXA';
            require dirname(__DIR__) . '/Views/auth/login.php';
            return;
        }

        Session::regenerate();
        Session::remove('republic');
        Session::set('user', $user);

        header('Location: /republica');
        exit;
    }

    public function logout(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            exit('Requisição inválida.');
        }

        Session::destroy();
        header('Location: /');
        exit;
    }
    private function activeSessionUser(): ?array
    {
        $sessionUser = Session::get('user');

        if (
            !is_array($sessionUser)
            || !isset($sessionUser['id'], $sessionUser['email'])
        ) {
            return null;
        }

        $user = $this->authService->findActiveSessionUser(
            (int) $sessionUser['id'],
            (string) $sessionUser['email']
        );

        if ($user === null) {
            Session::remove('user');
            Session::remove('republic');
            return null;
        }

        Session::set('user', $user);

        return $user;
    }

}
