<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ExpenseController;
use App\Controllers\ExpenseDetailsController;
use App\Controllers\ExpenseEditController;
use App\Controllers\ExpensePaymentController;
use App\Controllers\FinanceController;
use App\Controllers\MemberController;
use App\Controllers\ProfileController;
use App\Controllers\RepublicController;
use App\Middleware\AuthMiddleware;

return [

    'GET /' => function (): void {
        require dirname(__DIR__)
            . '/app/Views/landing.php';
    },

    'GET /dashboard' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('dashboard');
    },

    'GET /caixa' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('cash');
    },

    'POST /caixa' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('store');
    },

    'GET /relatorios' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('reports');
    },

    'GET /relatorios/exportar' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('export');
    },

    'GET /despesas' => function (): void {
        (new ExpenseController())->index();
    },

    'GET /despesas/nova' => function (): void {
        (new ExpenseController())->create();
    },

    'POST /despesas/nova' => function (): void {
        (new ExpenseController())->store();
    },

    'GET /despesas/editar' => function (): void {
        (new ExpenseEditController())->edit();
    },

    'POST /despesas/editar' => function (): void {
        (new ExpenseEditController())->edit();
    },

    'GET /despesas/detalhes' => function (): void {
        (new ExpenseDetailsController())->show();
    },

    'POST /despesas/pagamento' => function (): void {
        (new ExpensePaymentController())->store();
    },

    'POST /despesas/pagamento/confirmar' => function (): void {
        (new ExpensePaymentController())->confirm();
    },

    'GET /despesas/comprovante' => function (): void {
        (new ExpensePaymentController())->proof();
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

    'GET /republica/configuracoes' => function (): void {
        (new RepublicController())
            ->settings();
    },

    'POST /republica/configuracoes' => function (): void {
        (new RepublicController())
            ->updateSettings();
    },

    'GET /republica/moradores' => function (): void {
        (new MemberController())
            ->index();
    },

    'POST /republica/moradores/funcao' => function (): void {
        (new MemberController())
            ->changeRole();
    },

    'POST /republica/moradores/remover' => function (): void {
        (new MemberController())
            ->remove();
    },

    'GET /perfil' => function (): void {
        (new ProfileController())
            ->index();
    },

    'POST /perfil' => function (): void {
        (new ProfileController())
            ->update();
    },

    'POST /perfil/senha' => function (): void {
        (new ProfileController())
            ->changePassword();
    },

];
