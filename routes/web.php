<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\RepublicController;

return [

    'GET /' => function (): void {
        require dirname(__DIR__)
            . '/app/Views/landing.php';
    },

    'GET /dashboard' => function (): void {
        (new DashboardController())
            ->index();
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

    'GET /republica' => function (): void {
        (new RepublicController())
            ->choose();
    },

    'POST /republica/criar' => function (): void {
        (new RepublicController())
            ->create();
    },

    'POST /republica/entrar' => function (): void {
        (new RepublicController())
            ->join();
    },

];
