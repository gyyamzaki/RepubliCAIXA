<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Services\RepublicService;

final class DashboardController
{
    private RepublicService $republicService;

    public function __construct()
    {
        $this->republicService = new RepublicService();
    }

    public function index(): void
    {
        $user = AuthMiddleware::requireLogin();

        // Revalida a participação no banco em cada acesso ao painel.
        // Assim, uma sessão antiga não mantém acesso após a remoção da república.
        $republic = $this->republicService->findActiveByUser(
            (int) $user['id']
        );

        if ($republic === null) {
            Session::remove('republic');
            header('Location: /republica');
            exit;
        }

        Session::set('republic', $republic);

        $pageTitle = 'Dashboard - RepubliCAIXA';
        $currentPage = 'dashboard';

        require dirname(__DIR__) . '/Views/dashboard.php';
    }
}
