<?php

use App\Core\Csrf;
use App\Core\Session;

$currentPage = $currentPage ?? '';
$user = Session::get('user');
$logoutCsrfToken = Csrf::token();
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <a class="brand" href="/dashboard">
            <span class="brand-mark">R$</span>
            <span>RepubliCAIXA</span>
        </a>

        <button
            class="menu-toggle"
            type="button"
            aria-label="Abrir menu"
            aria-expanded="false"
            aria-controls="sidebar-navigation"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <nav
        class="sidebar-nav"
        id="sidebar-navigation"
        aria-label="Navegação principal"
    >
        <a class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/dashboard">
            Dashboard
        </a>

        <a class="<?= $currentPage === 'expenses' ? 'active' : '' ?>" href="#">
            Despesas
        </a>

        <a class="<?= $currentPage === 'cash' ? 'active' : '' ?>" href="#">
            Caixa
        </a>

        <a class="<?= $currentPage === 'members' ? 'active' : '' ?>" href="/republica/moradores">
            Moradores
        </a>

        <a class="<?= $currentPage === 'profile' ? 'active' : '' ?>" href="/perfil">
            Perfil
        </a>

        <?php if ($user): ?>
            <form action="/logout" method="post" class="sidebar-logout">
                <input
                    type="hidden"
                    name="_csrf"
                    value="<?= htmlspecialchars($logoutCsrfToken, ENT_QUOTES, 'UTF-8') ?>"
                >
                <button type="submit" class="sidebar-logout-button">
                    Sair
                </button>
            </form>
        <?php else: ?>
            <a href="/login">Entrar</a>
        <?php endif; ?>
    </nav>
</aside>
