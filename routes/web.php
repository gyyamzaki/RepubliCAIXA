
<?php

use App\Controllers\AuthController;
use App\Controllers\ExpenseController;
use App\Controllers\ExpenseEditController;
use App\Controllers\ExpenseDetailsController;
use App\Controllers\ExpensePaymentController;

return [

    // PAGINA INICIAL
    'GET /' => function (): void {
        require dirname(__DIR__)
            . '/app/Views/home.php';
    },

    // TELA 5 - LISTA DE DESPESAS
    'GET /despesas' => function (): void {
        (new ExpenseController())->index();
    },

    // TELA 6 - CADASTRAR DESPESA
    'GET /despesas/nova' => function (): void {
        (new ExpenseController())->create();
    },

    'POST /despesas/nova' => function (): void {
        (new ExpenseController())->store();
    },

    // TELA 6 - EDITAR DESPESA
    'GET /despesas/editar' => function (): void {
        (new ExpenseEditController())->edit();
    },

    'POST /despesas/editar' => function (): void {
        (new ExpenseEditController())->edit();
    },

    // TELA 7 - DETALHES DA DESPESA
    'GET /despesas/detalhes' => function (): void {
        (new ExpenseDetailsController())->show();
    },

    // REGISTRAR PAGAMENTO
    'POST /despesas/pagamento' => function (): void {
        (new ExpensePaymentController())->store();
    },

    // CONFIRMAR PAGAMENTO
    'POST /despesas/pagamento/confirmar' => function (): void {
        (new ExpensePaymentController())->confirm();
    },

    // BAIXAR COMPROVANTE
    'GET /despesas/comprovante' => function (): void {
        (new ExpensePaymentController())->proof();
    },

    // LOGIN E CADASTRO - INTEGRANTE 1
    'GET /cadastro' => function (): void {
        (new AuthController())->showRegister();
    },

    'POST /cadastro' => function (): void {
        (new AuthController())->register();
    },

    'GET /login' => function (): void {
        (new AuthController())->showLogin();
    },

    'POST /login' => function (): void {
        (new AuthController())->login();
    },

    'POST /logout' => function (): void {
        (new AuthController())->logout();
    },

];
