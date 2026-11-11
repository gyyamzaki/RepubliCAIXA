<?php

use App\Core\Session;

$pageTitle = 'RepubliCAIXA';
$currentPage = 'dashboard';
$user = Session::get('user');

require __DIR__ . '/partials/header.php';
?>

<div class="app-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <p class="eyebrow">Planejamento financeiro da casa</p>

                <h1>
                    <?php if ($user): ?>
                        Olá, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        RepubliCAIXA
                    <?php endif; ?>
                </h1>

                <p class="text-muted">
                    Organize as finanças da república de forma simples e transparente.
                </p>
            </div>
        </div>

        <section class="card-grid">
            <article class="card">
                <span class="card-label">Saldo da casa</span>
                <strong class="card-value">R$ 0,00</strong>
            </article>

            <article class="card">
                <span class="card-label">Despesas do mês</span>
                <strong class="card-value">R$ 0,00</strong>
            </article>

            <article class="card">
                <span class="card-label">Pendente</span>
                <strong class="card-value status-warning">R$ 0,00</strong>
            </article>
        </section>
    </main>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
