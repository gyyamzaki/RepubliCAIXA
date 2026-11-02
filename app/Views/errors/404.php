<?php
$pageTitle = 'Página não encontrada';
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="centered-page">
    <section class="card error-card">
        <p class="eyebrow">Erro 404</p>
        <h1>Página não encontrada</h1>
        <p class="text-muted">O endereço informado não existe ou ainda não foi desenvolvido.</p>
        <a class="button button-primary" href="/">Voltar ao início</a>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
