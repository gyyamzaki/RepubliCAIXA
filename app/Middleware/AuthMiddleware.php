<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;
use App\Services\AuthService;

final class AuthMiddleware
{
    public static function requireLogin(): array
    {
        $sessionUser = Session::get('user');

        if (
            !is_array($sessionUser)
            || !isset($sessionUser['id'], $sessionUser['email'])
        ) {
            self::redirectToLogin();
        }

        /*
         * Não confiamos apenas no conteúdo salvo na sessão.
         * Isso evita que uma sessão antiga continue válida depois
         * de um reset do banco ou da remoção/desativação do usuário.
         */
        $authService = new AuthService();

        $user = $authService->findActiveSessionUser(
            (int) $sessionUser['id'],
            (string) $sessionUser['email']
        );

        if ($user === null) {
            Session::destroy();

            header('Location: /login?session=expired');
            exit;
        }

        // Atualiza nome/e-mail da sessão caso tenham sido alterados no banco.
        Session::set('user', $user);

        return $user;
    }

    private static function redirectToLogin(): never
    {
        Session::remove('user');
        Session::remove('republic');

        header('Location: /login');
        exit;
    }
}
