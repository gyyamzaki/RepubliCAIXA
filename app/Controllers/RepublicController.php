<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Services\RepublicService;
use InvalidArgumentException;
use RuntimeException;

final class RepublicController
{
    private RepublicService $service;

    public function __construct()
    {
        $this->service = new RepublicService();
    }

    public function choose(): void
    {
        $user = AuthMiddleware::requireLogin();

        $republic = $this->service->findActiveByUser((int) $user['id']);

        if ($republic !== null) {
            Session::set('republic', $republic);
            header('Location: /dashboard');
            exit;
        }

        $this->render();
    }

    public function create(): void
    {
        $user = AuthMiddleware::requireLogin();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            $this->render([
                'Requisição inválida. Atualize a página e tente novamente.',
            ]);
            return;
        }

        $name = trim($_POST['name'] ?? '');

        try {
            $republic = $this->service->create((int) $user['id'], $name);
            Session::set('republic', $republic);
            header('Location: /dashboard');
            exit;
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->render([$exception->getMessage()], $name);
        }
    }

    public function join(): void
    {
        $user = AuthMiddleware::requireLogin();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            $this->render([
                'Requisição inválida. Atualize a página e tente novamente.',
            ]);
            return;
        }

        $inviteCode = strtoupper(trim($_POST['invite_code'] ?? ''));

        try {
            $republic = $this->service->join((int) $user['id'], $inviteCode);
            Session::set('republic', $republic);
            header('Location: /dashboard');
            exit;
        } catch (InvalidArgumentException $exception) {
            $this->render([$exception->getMessage()], '', $inviteCode);
        }
    }

    private function render(
        array $errors = [],
        string $name = '',
        string $inviteCode = ''
    ): void {
        $pageTitle = 'Sua República - RepubliCAIXA';
        $csrfToken = Csrf::token();

        require dirname(__DIR__) . '/Views/republic/choose.php';
    }
}
