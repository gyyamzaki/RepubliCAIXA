<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

final class AuthMiddleware
{
    public static function requireLogin(): array
    {
        $user = Session::get('user');

        if (!is_array($user)) {
            header('Location: /login');
            exit;
        }

        return $user;
    }
}
