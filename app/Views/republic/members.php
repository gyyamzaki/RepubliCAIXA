<?php

use App\Core\Session;

$user = Session::get('user');
$isAdmin = $republic['role'] === 'admin';

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header page-header-actions">
            <div>
                <p class="eyebrow">Sua república</p>
                <h1><?= htmlspecialchars($republic['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted">
                    Veja quem faz parte da casa e gerencie os moradores.
                </p>
            </div>

            <a class="button button-secondary" href="/republica/configuracoes">
                Configurações da república
            </a>
        </div>

        <?php if (is_array($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <section class="card invite-card">
            <div>
                <span class="card-label">Código de convite</span>
                <strong class="invite-code">
                    <?= htmlspecialchars($republic['invite_code'], ENT_QUOTES, 'UTF-8') ?>
                </strong>
                <p class="text-muted">
                    Compartilhe este código com quem vai entrar na república.
                </p>
            </div>

            <button
                type="button"
                class="button button-primary"
                data-copy-invite
                data-invite-code="<?= htmlspecialchars($republic['invite_code'], ENT_QUOTES, 'UTF-8') ?>"
            >
                Copiar código
            </button>
        </section>

        <section class="members-section">
            <div class="section-title">
                <div>
                    <h2>Moradores</h2>
                    <p class="text-muted"><?= count($members) ?> participante(s)</p>
                </div>
            </div>

            <div class="members-list">
                <?php foreach ($members as $member): ?>
                    <?php
                    $isCurrentUser =
                        (int) $member['user_id'] === (int) $user['id'];
                    ?>

                    <article class="member-card">
                        <div class="member-info">
                            <div class="member-avatar">
                                <?= htmlspecialchars(
                                    strtoupper(substr($member['name'], 0, 1)),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>

                            <div>
                                <strong>
                                    <?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="text-muted">(você)</span>
                                    <?php endif; ?>
                                </strong>

                                <span class="member-email">
                                    <?= htmlspecialchars($member['email'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="role-badge <?= $member['role'] === 'admin' ? 'role-admin' : 'role-member' ?>">
                                <?= $member['role'] === 'admin' ? 'Administrador' : 'Morador' ?>
                            </span>
                        </div>

                        <?php if ($isAdmin && !$isCurrentUser): ?>
                            <div class="member-actions">
                                <form action="/republica/moradores/funcao" method="post">
                                    <input
                                        type="hidden"
                                        name="_csrf"
                                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="member_id"
                                        value="<?= (int) $member['member_id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="role"
                                        value="<?= $member['role'] === 'admin' ? 'member' : 'admin' ?>"
                                    >

                                    <button type="submit" class="button button-secondary">
                                        <?= $member['role'] === 'admin' ? 'Tornar morador' : 'Tornar admin' ?>
                                    </button>
                                </form>

                                <form
                                    action="/republica/moradores/remover"
                                    method="post"
                                    data-confirm="Deseja realmente remover este morador?"
                                >
                                    <input
                                        type="hidden"
                                        name="_csrf"
                                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="member_id"
                                        value="<?= (int) $member['member_id'] ?>"
                                    >

                                    <button type="submit" class="button button-danger">
                                        Remover
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
