<?php

use App\Core\Session;

$currentPage = $currentPage ?? '';
$user = Session::get('user');
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

        <a class="<?= $currentPage === 'members' ? 'active' : '' ?>" href="#">
            Moradores
        </a>

        <a class="<?= $currentPage === 'profile' ? 'active' : '' ?>" href="#">
            Perfil
        </a>

        <?php if ($user): ?>
            <form action="/logout" method="post" class="sidebar-logout">
                <button type="submit" class="sidebar-logout-button">
                    Sair
                </button>
            </form>
        <?php else: ?>
            <a href="/login">Entrar</a>
        <?php endif; ?>
    </nav>
</aside>
