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

        $republic = Session::get('republic');

        if (!is_array($republic)) {
            $republic = $this->republicService->findActiveByUser(
                (int) $user['id']
            );

            if ($republic === null) {
                header('Location: /republica');
                exit;
            }

            Session::set('republic', $republic);
        }

        $pageTitle = 'Dashboard - RepubliCAIXA';
        $currentPage = 'dashboard';

        require dirname(__DIR__) . '/Views/dashboard.php';
    }
}
