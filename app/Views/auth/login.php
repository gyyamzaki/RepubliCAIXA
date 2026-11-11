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
                Entrar
            </h1>

            <p class="text-muted">
                Acesse sua conta para continuar.
            </p>

        </div>

        <?php if (
            isset($_GET['registered'])
        ): ?>

            <div class="alert alert-success">
                Conta criada com sucesso.
                Faça seu login.
            </div>

        <?php endif; ?>

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
            action="/login"
            method="post"
            class="form"
        >

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
                    required
                >

            </div>

            <button
                type="submit"
                class="button button-primary button-full"
            >
                Entrar
            </button>

        </form>

        <p class="auth-footer">

            Ainda não possui uma conta?

            <a href="/cadastro">
                Cadastre-se
            </a>

        </p>

    </section>

</main>

<?php
require dirname(__DIR__)
    . '/partials/footer.php';
?>