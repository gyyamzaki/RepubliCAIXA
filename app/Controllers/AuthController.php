<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Services\AuthService;
use InvalidArgumentException;

final class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService =
            new AuthService();
    }

    public function showRegister(): void
    {
        $pageTitle =
            'Criar conta - RepubliCAIXA';

        $errors = [];
        $name = '';
        $email = '';

        require dirname(__DIR__)
            . '/Views/auth/register.php';
    }

    public function register(): void
    {
        $name =
            trim($_POST['name'] ?? '');

        $email =
            trim($_POST['email'] ?? '');

        $password =
            $_POST['password'] ?? '';

        $passwordConfirmation =
            $_POST['password_confirmation']
            ?? '';

        $errors = [];

        if (strlen($name) < 2) {
            $errors[] =
                'Informe um nome válido.';
        }

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors[] =
                'Informe um e-mail válido.';
        }

        if (strlen($password) < 8) {
            $errors[] =
                'A senha deve possuir pelo menos 8 caracteres.';
        }

        if (
            $password
            !== $passwordConfirmation
        ) {
            $errors[] =
                'As senhas não coincidem.';
        }

        if (!empty($errors)) {
            $pageTitle =
                'Criar conta - RepubliCAIXA';

            require dirname(__DIR__)
                . '/Views/auth/register.php';

            return;
        }

        try {

            $this->authService->register(
                $name,
                $email,
                $password
            );

        } catch (InvalidArgumentException $exception) {

            $errors[] =
                $exception->getMessage();

            $pageTitle =
                'Criar conta - RepubliCAIXA';

            require dirname(__DIR__)
                . '/Views/auth/register.php';

            return;
        }

        header(
            'Location: /login?registered=1'
        );

        exit;
    }

    public function showLogin(): void
    {
        $pageTitle =
            'Login - RepubliCAIXA';

        $errors = [];
        $email = '';

        require dirname(__DIR__)
            . '/Views/auth/login.php';
    }

    public function login(): void
    {
        $email =
            trim($_POST['email'] ?? '');

        $password =
            $_POST['password'] ?? '';

        $errors = [];

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors[] =
                'Informe um e-mail válido.';
        }

        if ($password === '') {
            $errors[] =
                'Informe sua senha.';
        }

        if (!empty($errors)) {
            $pageTitle =
                'Login - RepubliCAIXA';

            require dirname(__DIR__)
                . '/Views/auth/login.php';

            return;
        }

        $user =
            $this->authService
                ->authenticate(
                    $email,
                    $password
                );

        if ($user === null) {

            $errors[] =
                'E-mail ou senha incorretos.';

            $pageTitle =
                'Login - RepubliCAIXA';

            require dirname(__DIR__)
                . '/Views/auth/login.php';

            return;
        }

        Session::regenerate();

        Session::set(
            'user',
            $user
        );

        header('Location: /republica');
        exit;
    }

    public function logout(): void
    {
        Session::destroy();

        header('Location: /');
        exit;
    }
}