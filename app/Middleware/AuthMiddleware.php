<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Session;
use PDO;

final class AuthMiddleware
{
    public static function requireLogin(): array
    {
        // Verifica se existe usuário na sessão
        $sessionUser = Session::get('user');

        if (
            !is_array($sessionUser)
            || empty($sessionUser['id'])
            || empty($sessionUser['email'])
        ) {
            self::redirectToLogin();
        }

        // Confere se o usuário continua ativo no MySQL
        $db = Database::connection();

        $query = $db->prepare(
            'SELECT id, name, email
             FROM users
             WHERE id = :id
               AND email = :email
               AND active = 1
             LIMIT 1'
        );

        $query->execute([
            'id' => (int) $sessionUser['id'],
            'email' => (string) $sessionUser['email']
        ]);

        $user = $query->fetch(PDO::FETCH_ASSOC);

        // Se o usuário foi excluído ou desativado,
        // encerra a sessão e impede o acesso.
        if (!$user) {
            Session::destroy();

            header('Location: /login?session=expired');
            exit;
        }

        // Atualiza os dados da sessão
        $user['id'] = (int) $user['id'];

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
