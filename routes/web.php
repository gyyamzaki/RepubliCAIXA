
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

    // PAGINA INICIAL - PUBLICA
    'GET /' => function (): void {
        require dirname(__DIR__)
            . '/app/Views/landing.php';
    },

    // DASHBOARD
    'GET /dashboard' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('dashboard');
    },

    // CAIXA
    'GET /caixa' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('cash');
    },

    'POST /caixa' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('store');
    },

    // RELATORIOS
    'GET /relatorios' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('reports');
    },

    'GET /relatorios/exportar' => function (): void {
        AuthMiddleware::requireLogin();
        (new FinanceController())->handle('export');
    },

    // DESPESAS - LISTAGEM
    'GET /despesas' => function (): void {
        AuthMiddleware::requireLogin();
        (new ExpenseController())->index();
    },

    // DESPESAS - CADASTRO
    'GET /despesas/nova' => function (): void {
        AuthMiddleware::requireLogin();
        (new ExpenseController())->create();
    },

    'POST /despesas/nova' => function (): void {
        AuthMiddleware::requireLogin();
        (new ExpenseController())->store();
    },

    // DESPESAS - EDICAO
    'GET /despesas/editar' => function (): void {
        AuthMiddleware::requireLogin();
        (new ExpenseEditController())->edit();
    },

    'POST /despesas/editar' => function (): void {
        AuthMiddleware::requireLogin();
        (new ExpenseEditController())->edit();
    },

    // DESPESAS - DETALHES
    'GET /despesas/detalhes' => function (): void {
        AuthMiddleware::requireLogin();
        (new ExpenseDetailsController())->show();
    },

    // PAGAMENTOS - REGISTRO
    'POST /despesas/pagamento' => function (): void {
        AuthMiddleware::requireLogin();
        (new ExpensePaymentController())->store();
    },

    // PAGAMENTOS - CONFIRMACAO
    'POST /despesas/pagamento/confirmar' => function (): void {
        AuthMiddleware::requireLogin();
        (new ExpensePaymentController())->confirm();
    },

    // COMPROVANTES
    'GET /despesas/comprovante' => function (): void {
        AuthMiddleware::requireLogin();
        (new ExpensePaymentController())->proof();
    },

    // AUTENTICACAO - ROTAS PUBLICAS
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

    // REPUBLICAS
    'GET /republica' => function (): void {
        (new RepublicController())->choose();
    },

    'POST /republica/criar' => function (): void {
        (new RepublicController())->create();
    },

    'POST /republica/entrar' => function (): void {
        (new RepublicController())->join();
    },

    'GET /republica/configuracoes' => function (): void {
        (new RepublicController())->settings();
    },

    'POST /republica/configuracoes' => function (): void {
        (new RepublicController())->updateSettings();
    },

    // MORADORES
    'GET /republica/moradores' => function (): void {
        (new MemberController())->index();
    },

    'POST /republica/moradores/funcao' => function (): void {
        (new MemberController())->changeRole();
    },

    'POST /republica/moradores/remover' => function (): void {
        (new MemberController())->remove();
    },

    // PERFIL
    'GET /perfil' => function (): void {
        (new ProfileController())->index();
    },

    'POST /perfil' => function (): void {
        (new ProfileController())->update();
    },

    'POST /perfil/senha' => function (): void {
        (new ProfileController())->changePassword();
    },

];
