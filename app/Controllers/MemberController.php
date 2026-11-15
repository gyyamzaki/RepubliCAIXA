<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Services\MemberService;
use App\Services\RepublicService;
use InvalidArgumentException;

final class MemberController
{
    private MemberService $memberService;
    private RepublicService $republicService;

    public function __construct()
    {
        $this->memberService =
            new MemberService();

        $this->republicService =
            new RepublicService();
    }

    public function index(): void
    {
        $user =
            AuthMiddleware::requireLogin();

        $republic =
            $this->republicService
                ->findActiveByUser(
                    (int) $user['id']
                );

        if ($republic === null) {
            Session::remove('republic');

            header(
                'Location: /republica'
            );

            exit;
        }

        Session::set(
            'republic',
            $republic
        );

        $members =
            $this->memberService
                ->listMembers(
                    (int) $republic['id']
                );

        $csrfToken =
            Csrf::token();

        $flash =
            Session::get('flash');

        Session::remove('flash');

        $pageTitle =
            'Moradores - RepubliCAIXA';

        $currentPage =
            'members';

        require dirname(__DIR__)
            . '/Views/republic/members.php';
    }

    public function changeRole(): void
    {
        $user =
            AuthMiddleware::requireLogin();

        if (
            !Csrf::validate(
                $_POST['_csrf']
                ?? null
            )
        ) {
            $this->flash(
                'danger',
                'Requisição inválida.'
            );

            $this->redirect();
        }

        $republic =
            $this->republicService
                ->findActiveByUser(
                    (int) $user['id']
                );

        if ($republic === null) {
            header(
                'Location: /republica'
            );

            exit;
        }

        $memberId =
            (int) (
                $_POST['member_id']
                ?? 0
            );

        $role =
            $_POST['role']
            ?? '';

        try {

            $this->memberService
                ->changeRole(
                    (int) $republic['id'],
                    (int) $user['id'],
                    $memberId,
                    $role
                );

            $this->flash(
                'success',
                'Função do morador atualizada.'
            );

        } catch (
            InvalidArgumentException
            $exception
        ) {
            $this->flash(
                'danger',
                $exception->getMessage()
            );
        }

        $this->redirect();
    }

    public function remove(): void
    {
        $user =
            AuthMiddleware::requireLogin();

        if (
            !Csrf::validate(
                $_POST['_csrf']
                ?? null
            )
        ) {
            $this->flash(
                'danger',
                'Requisição inválida.'
            );

            $this->redirect();
        }

        $republic =
            $this->republicService
                ->findActiveByUser(
                    (int) $user['id']
                );

        if ($republic === null) {
            header(
                'Location: /republica'
            );

            exit;
        }

        $memberId =
            (int) (
                $_POST['member_id']
                ?? 0
            );

        try {

            $this->memberService
                ->removeMember(
                    (int) $republic['id'],
                    (int) $user['id'],
                    $memberId
                );

            $this->flash(
                'success',
                'Morador removido da república.'
            );

        } catch (
            InvalidArgumentException
            $exception
        ) {
            $this->flash(
                'danger',
                $exception->getMessage()
            );
        }

        $this->redirect();
    }

    private function flash(
        string $type,
        string $message
    ): void {
        Session::set(
            'flash',
            [
                'type' => $type,
                'message' => $message,
            ]
        );
    }

    private function redirect(): never
    {
        header(
            'Location: /republica/moradores'
        );

        exit;
    }
}