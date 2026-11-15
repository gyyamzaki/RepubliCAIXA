<?php

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header page-header-actions">
            <div>
                <p class="eyebrow">Sua república</p>
                <h1>Configurações</h1>
                <p class="text-muted">
                    Consulte os dados da casa e, se você for administrador, altere as informações principais.
                </p>
            </div>

            <a class="button button-secondary" href="/republica/moradores">
                Ver moradores
            </a>
        </div>

        <?php if (is_array($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid settings-grid-single">
            <section class="card settings-card">
                <div class="settings-card-header">
                    <div>
                        <h2>Dados da república</h2>
                        <p class="text-muted">
                            <?= $isAdmin
                                ? 'Como administrador, você pode editar os dados abaixo.'
                                : 'Somente administradores podem alterar estes dados.' ?>
                        </p>
                    </div>

                    <span class="role-badge <?= $isAdmin ? 'role-admin' : 'role-member' ?>">
                        <?= $isAdmin ? 'Administrador' : 'Morador' ?>
                    </span>
                </div>

                <form action="/republica/configuracoes" method="post" class="form">
                    <input
                        type="hidden"
                        name="_csrf"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="form-group">
                        <label for="republic-name">Nome</label>
                        <input
                            type="text"
                            id="republic-name"
                            name="name"
                            maxlength="120"
                            value="<?= htmlspecialchars($republic['name'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isAdmin ? '' : 'disabled' ?>
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="republic-description">Descrição</label>
                        <textarea
                            id="republic-description"
                            name="description"
                            maxlength="255"
                            rows="4"
                            placeholder="Ex.: República próxima à universidade"
                            <?= $isAdmin ? '' : 'disabled' ?>
                        ><?= htmlspecialchars((string) ($republic['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="republic-invite-code">Código de convite</label>
                        <div class="inline-copy-field">
                            <input
                                type="text"
                                id="republic-invite-code"
                                value="<?= htmlspecialchars($republic['invite_code'], ENT_QUOTES, 'UTF-8') ?>"
                                readonly
                            >
                            <button
                                type="button"
                                class="button button-secondary"
                                data-copy-invite
                                data-invite-code="<?= htmlspecialchars($republic['invite_code'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                Copiar
                            </button>
                        </div>
                    </div>

                    <?php if ($isAdmin): ?>
                        <button type="submit" class="button button-primary">
                            Salvar alterações
                        </button>
                    <?php endif; ?>
                </form>
            </section>
        </div>
    </main>
</div>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
