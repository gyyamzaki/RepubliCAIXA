<?php

$pageTitle = 'RepubliCAIXA';
$currentPage = 'dashboard';
require __DIR__ . '/partials/header.php';
?>

<div class="app-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <p class="eyebrow">Planejamento financeiro da casa</p>
                <h1>RepubliCAIXA</h1>
                <p class="text-muted">Base inicial do projeto. Cada módulo será desenvolvido nas branches do grupo.</p>
            </div>
        </div>

        <section class="card-grid">
            <article class="card"><span class="card-label">Saldo da casa</span><strong class="card-value">R$ 0,00</strong></article>
            <article class="card"><span class="card-label">Despesas do mês</span><strong class="card-value">R$ 0,00</strong></article>
            <article class="card"><span class="card-label">Pendente</span><strong class="card-value status-warning">R$ 0,00</strong></article>
        </section>
    </main>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
