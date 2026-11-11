<?php

use App\Controllers\AuthController;

return [

    'GET /' => function (): void {
        require dirname(__DIR__)
            . '/app/Views/home.php';
    },

    'GET /cadastro' => function (): void {
        (new AuthController())
            ->showRegister();
    },

    'POST /cadastro' => function (): void {
        (new AuthController())
            ->register();
    },

    'GET /login' => function (): void {
        (new AuthController())
            ->showLogin();
    },

    'POST /login' => function (): void {
        (new AuthController())
            ->login();
    },

    'POST /logout' => function (): void {
        (new AuthController())
            ->logout();
    },

];