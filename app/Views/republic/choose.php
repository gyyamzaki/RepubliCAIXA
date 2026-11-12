<?php

require dirname(__DIR__) . '/partials/header.php';
?>

<main class="republic-page">
    <section class="republic-container">
        <header class="republic-header">
            <p class="eyebrow">Primeiros passos</p>
            <h1>Sua república</h1>
            <p class="text-muted">
                Crie uma nova república ou entre em uma já existente.
            </p>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p>
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="republic-choice">
            <article class="card">
                <h2>Criar uma república</h2>
                <p class="text-muted">
                    Você será o administrador da nova república.
                </p>

                <form action="/republica/criar" method="post" class="form">
                    <input
                        type="hidden"
                        name="_csrf"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="form-group">
                        <label for="name">Nome da república</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="120"
                            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ex.: República Tatooine"
                            required
                        >
                    </div>

                    <button
                        class="button button-primary button-full"
                        type="submit"
                    >
                        Criar república
                    </button>
                </form>
            </article>

            <article class="card">
                <h2>Entrar em uma república</h2>
                <p class="text-muted">
                    Use o código enviado pelo administrador.
                </p>

                <form action="/republica/entrar" method="post" class="form">
                    <input
                        type="hidden"
                        name="_csrf"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="form-group">
                        <label for="invite_code">Código de convite</label>
                        <input
                            type="text"
                            id="invite_code"
                            name="invite_code"
                            maxlength="12"
                            value="<?= htmlspecialchars($inviteCode, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ex.: 4F8A2C1D"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <button
                        class="button button-primary button-full"
                        type="submit"
                    >
                        Entrar
                    </button>
                </form>
            </article>
        </div>
    </section>
</main>

<?php
require dirname(__DIR__) . '/partials/footer.php';
?>
