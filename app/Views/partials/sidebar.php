<?php
$currentPage = $currentPage ?? '';
?>
<aside class="sidebar">
    <a class="brand" href="/">
        <span class="brand-mark">R$</span>
        <span>RepubliCAIXA</span>
    </a>

    <nav class="sidebar-nav" aria-label="Navegação principal">
        <a class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/">Dashboard</a>
        <a class="<?= $currentPage === 'expenses' ? 'active' : '' ?>" href="#">Despesas</a>
        <a class="<?= $currentPage === 'cash' ? 'active' : '' ?>" href="#">Caixa</a>
        <a class="<?= $currentPage === 'members' ? 'active' : '' ?>" href="#">Moradores</a>
        <a class="<?= $currentPage === 'profile' ? 'active' : '' ?>" href="#">Perfil</a>
    </nav>
</aside>
