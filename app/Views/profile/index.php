<?php

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <p class="eyebrow">Sua conta</p>
            <h1>Meu perfil</h1>
            <p class="text-muted">
                Atualize seus dados pessoais e sua senha de acesso.
            </p>
        </div>

        <?php if (is_array($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">
            <section class="card settings-card">
                <h2>Dados pessoais</h2>
                <p class="text-muted">Essas informações identificam sua conta no sistema.</p>

                <form action="/perfil" method="post" class="form">
                    <input
                        type="hidden"
                        name="_csrf"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="form-group">
                        <label for="profile-name">Nome</label>
                        <input
                            type="text"
                            id="profile-name"
                            name="name"
                            maxlength="120"
                            value="<?= htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="profile-email">E-mail</label>
                        <input
                            type="email"
                            id="profile-email"
                            name="email"
                            maxlength="180"
                            value="<?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="profile-phone">Telefone</label>
                        <input
                            type="tel"
                            id="profile-phone"
                            name="phone"
                            maxlength="30"
                            value="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ex.: (15) 99999-9999"
                        >
                    </div>

                    <button type="submit" class="button button-primary">
                        Salvar dados
                    </button>
                </form>
            </section>

            <section class="card settings-card">
                <h2>Alterar senha</h2>
                <p class="text-muted">
                    Para sua segurança, informe a senha atual antes de definir uma nova.
                </p>

                <form action="/perfil/senha" method="post" class="form">
                    <input
                        type="hidden"
                        name="_csrf"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="form-group">
                        <label for="current-password">Senha atual</label>
                        <input
                            type="password"
                            id="current-password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="new-password">Nova senha</label>
                        <input
                            type="password"
                            id="new-password"
                            name="new_password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password-confirmation">Confirmar nova senha</label>
                        <input
                            type="password"
                            id="password-confirmation"
                            name="password_confirmation"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="button button-secondary">
                        Alterar senha
                    </button>
                </form>
            </section>
        </div>
    </main>
</div>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
