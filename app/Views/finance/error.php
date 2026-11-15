<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<main class="centered-page"><section class="card error-card" role="alert"><h1>Não foi possível continuar</h1><p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p><a class="button button-primary" href="/caixa">Abrir Caixa</a> <a href="/login">Entrar novamente</a></section></main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
