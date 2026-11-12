<?php

require dirname(__DIR__)
    . '/partials/header.php';
?>

<main class="auth-page">

    <section class="auth-card">

        <a
            class="auth-brand"
            href="/"
        >
            <span class="brand-mark">
                R$
            </span>

            <span>
                RepubliCAIXA
            </span>
        </a>

        <div class="auth-header">

            <h1>
                Criar conta
            </h1>

            <p class="text-muted">
                Cadastre-se para começar
                a organizar sua república.
            </p>

        </div>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <form
            action="/cadastro"
            method="post"
            class="form"
        >

            <div class="form-group">

                <label for="name">
                    Nome
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars(
                        $name,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Senha
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                >

            </div>

            <div class="form-group">

                <label
                    for="password_confirmation"
                >
                    Confirmar senha
                </label>

                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    minlength="8"
                    required
                >

            </div>

            <button
                type="submit"
                class="button button-primary button-full"
            >
                Criar conta
            </button>

        </form>

        <p class="auth-footer">

            Já possui uma conta?

            <a href="/login">
                Entrar
            </a>

        </p>

    </section>

</main>

<?php
require dirname(__DIR__)
    . '/partials/footer.php';
?>