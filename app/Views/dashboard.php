<?php

use App\Core\Session;

$user = Session::get('user');
$republic = Session::get('republic');

require __DIR__ . '/partials/header.php';
?>

<div class="app-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header dashboard-heading">
            <div>
                <p class="eyebrow">Planejamento financeiro da casa</p>

                <h1>
                    Olá,
                    <?= htmlspecialchars(
                        (string) ($user['name'] ?? 'morador'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h1>

                <p class="text-muted">
                    <?= htmlspecialchars(
                        (string) ($republic['name'] ?? 'Sua república'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                    · organize as finanças da casa de forma simples e transparente.
                </p>
            </div>
        </div>

        <section class="card-grid" aria-label="Resumo financeiro">
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
