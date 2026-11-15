<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Services\ProfileService;
use InvalidArgumentException;

final class ProfileController
{
    private ProfileService $service;

    public function __construct()
    {
        $this->service = new ProfileService();
    }

    public function index(): void
    {
        $sessionUser = AuthMiddleware::requireLogin();
        $profile = $this->service->findById((int) $sessionUser['id']);

        if ($profile === null) {
            Session::destroy();
            header('Location: /login');
            exit;
        }

        $csrfToken = Csrf::token();
        $flash = Session::get('flash');
        Session::remove('flash');

        $pageTitle = 'Perfil - RepubliCAIXA';
        $currentPage = 'profile';

        require dirname(__DIR__) . '/Views/profile/index.php';
    }

    public function update(): void
    {
        $sessionUser = AuthMiddleware::requireLogin();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->flash('danger', 'Requisição inválida. Atualize a página e tente novamente.');
            $this->redirect();
        }

        try {
            $profile = $this->service->updateProfile(
                (int) $sessionUser['id'],
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['phone'] ?? '')
            );

            Session::set('user', [
                'id' => $profile['id'],
                'name' => $profile['name'],
                'email' => $profile['email'],
            ]);

            $this->flash('success', 'Perfil atualizado com sucesso.');
        } catch (InvalidArgumentException $exception) {
            $this->flash('danger', $exception->getMessage());
        }

        $this->redirect();
    }

    public function changePassword(): void
    {
        $sessionUser = AuthMiddleware::requireLogin();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->flash('danger', 'Requisição inválida. Atualize a página e tente novamente.');
            $this->redirect();
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($newPassword !== $confirmation) {
            $this->flash('danger', 'A confirmação da nova senha não coincide.');
            $this->redirect();
        }

        try {
            $this->service->changePassword(
                (int) $sessionUser['id'],
                $currentPassword,
                $newPassword
            );

            Session::regenerate();
            $this->flash('success', 'Senha alterada com sucesso.');
        } catch (InvalidArgumentException $exception) {
            $this->flash('danger', $exception->getMessage());
        }

        $this->redirect();
    }

    private function flash(string $type, string $message): void
    {
        Session::set('flash', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    private function redirect(): never
    {
        header('Location: /perfil');
        exit;
    }
}
